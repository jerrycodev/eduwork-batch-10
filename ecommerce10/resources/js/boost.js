import NProgress from "nprogress";
import "nprogress/nprogress.css";

const REQUEST_HEADERS = {
    Accept: "text/html, application/xhtml+xml",
    "X-Requested-With": "XMLHttpRequest",
    "X-Boost-Request": "true",
};

const UNSAFE_METHODS = new Set(["POST", "PUT", "PATCH", "DELETE"]);
const loadedScripts = new Set();
const trustedScriptOrigins = new Set([window.location.origin]);
const submittingForms = new WeakSet();

let navigationId = 0;

NProgress.configure({
    minimum: 0.08,
    easing: "ease",
    speed: 200,
    showSpinner: false,
    trickle: true,
    trickleSpeed: 200,
});

function dispatch(name, detail = {}) {
    document.dispatchEvent(new CustomEvent(name, { detail }));
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? "";
}

function isSameOrigin(value) {
    try {
        const url = new URL(value, window.location.href);

        return (
            url.origin === window.location.origin &&
            ["http:", "https:"].includes(url.protocol)
        );
    } catch {
        return false;
    }
}

function isBoostEnabled(element) {
    if (!(element instanceof Element)) return false;

    if (element.closest('[data-boost="false"]')) return false;

    return Boolean(element.closest('[data-boost="true"]'));
}

function shouldBoostLink(anchor, event) {
    if (!isBoostEnabled(anchor)) return false;

    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey
    ) {
        return false;
    }

    if (
        anchor.hasAttribute("download") ||
        anchor.hasAttribute("data-boost-ignore") ||
        anchor.getAttribute("aria-disabled") === "true"
    ) {
        return false;
    }

    const target = anchor.getAttribute("target");

    if (target && target.toLowerCase() !== "_self") return false;
    if (anchor.relList.contains("external")) return false;

    const href = anchor.getAttribute("href");

    if (!href || href.startsWith("#")) return false;
    if (!isSameOrigin(anchor.href)) return false;

    const destination = new URL(anchor.href);
    const current = new URL(window.location.href);

    // Keep native same-page anchor navigation.
    if (
        destination.pathname === current.pathname &&
        destination.search === current.search &&
        destination.hash
    ) {
        return false;
    }

    return true;
}

function shouldBoostForm(form, event) {
    if (!isBoostEnabled(form)) return false;

    if (
        event.defaultPrevented ||
        form.hasAttribute("data-boost-ignore")
    ) {
        return false;
    }

    const submitter = event.submitter;
    const target = submitter?.hasAttribute("formtarget")
        ? submitter.formTarget
        : form.target;
    const method = (
        submitter?.hasAttribute("formmethod")
            ? submitter.formMethod
            : form.method || "GET"
    ).toUpperCase();
    const action = submitter?.hasAttribute("formaction")
        ? submitter.formAction
        : form.action || window.location.href;
    const enctype = submitter?.hasAttribute("formenctype")
        ? submitter.formEnctype
        : form.enctype;

    if (target && target.toLowerCase() !== "_self") return false;
    if (!["GET", "POST"].includes(method)) return false;
    if (method === "POST" && enctype === "text/plain") return false;
    if (!isSameOrigin(action)) return false;

    return true;
}

function createFormData(form, submitter) {
    try {
        return submitter
            ? new FormData(form, submitter)
            : new FormData(form);
    } catch {
        return new FormData(form);
    }
}

function buildFormRequest(form, submitter) {
    const method = (
        submitter?.hasAttribute("formmethod")
            ? submitter.formMethod
            : form.method || "GET"
    ).toUpperCase();
    const action = submitter?.hasAttribute("formaction")
        ? submitter.formAction
        : form.action || window.location.href;
    const url = new URL(action);

    const formData = createFormData(form, submitter);

    const options = {
        method,
        headers: { ...REQUEST_HEADERS },
        credentials: "same-origin",
        mode: "same-origin",
        redirect: "follow",
        cache: "no-store",
        referrerPolicy: "same-origin",
    };

    if (method === "GET") {
        for (const [key, value] of formData.entries()) {
            // File inputs are not meaningful GET parameters.
            if (value instanceof File) continue;

            url.searchParams.append(key, value);
        }
    } else {
        options.body = formData;

        const token = getCsrfToken();

        if (UNSAFE_METHODS.has(method) && token) {
            options.headers["X-CSRF-TOKEN"] = token;
        }
    }

    return { url: url.href, options };
}

function sanitizeDocument(doc) {
    // The fetched response is same-origin, but do not trust executable
    // markup or dangerous URL attributes merely because of its origin.
    doc.querySelectorAll("base, object, embed, iframe").forEach((node) => {
        node.remove();
    });

    doc.querySelectorAll("*").forEach((element) => {
        for (const attribute of Array.from(element.attributes)) {
            const name = attribute.name.toLowerCase();
            const value = attribute.value.trim();

            if (
                name.startsWith("on") ||
                name === "srcdoc"
            ) {
                element.removeAttribute(attribute.name);
                continue;
            }

            const isNavigationUrl = ["href", "action", "formaction", "xlink:href"].includes(name);
            const isResourceUrl = name === "src";
            const isDangerousScheme = /^\s*(javascript|vbscript):/i.test(value);
            const isDataDocument = /^\s*data:/i.test(value) &&
                (isNavigationUrl || !/^\s*data:image\//i.test(value));

            if (
                (isNavigationUrl || isResourceUrl) &&
                (isDangerousScheme || isDataDocument)
            ) {
                element.removeAttribute(attribute.name);
            }
        }
    });

    // Inline scripts are never executed during a Boost navigation.
    // JSON data scripts remain in the document for page-specific modules.
    doc.body.querySelectorAll("script:not([src])").forEach((script) => {
        const type = (script.getAttribute("type") || "").toLowerCase();

        if (
            type !== "application/json" &&
            type !== "application/ld+json"
        ) {
            script.remove();
        }
    });
}

function rememberExistingScripts() {
    document.querySelectorAll("script[src]").forEach((script) => {
        try {
            const url = new URL(script.src, document.baseURI);

            // Script origins already present in the server-rendered initial
            // document are trusted (including the configured Vite dev server).
            trustedScriptOrigins.add(url.origin);
            loadedScripts.add(url.href);
        } catch {
            // Ignore malformed script URLs.
        }
    });
}

function loadExternalScript(oldScript, baseUrl) {
    const source = oldScript.getAttribute("src");

    if (!source) return Promise.resolve();

    const url = new URL(source, baseUrl);

    // Only allow same-origin scripts or origins explicitly present in the
    // trusted initial document (for example, the configured Vite dev server).
    if (!trustedScriptOrigins.has(url.origin)) {
        oldScript.remove();
        return Promise.resolve();
    }

    if (loadedScripts.has(url.href)) {
        oldScript.remove();
        return Promise.resolve();
    }

    loadedScripts.add(url.href);

    return new Promise((resolve, reject) => {
        const script = document.createElement("script");

        for (const attribute of Array.from(oldScript.attributes)) {
            script.setAttribute(attribute.name, attribute.value);
        }

        script.onload = () => {
            oldScript.remove();
            resolve();
        };

        script.onerror = () => {
            loadedScripts.delete(url.href);
            oldScript.remove();
            reject(new Error("A page script could not be loaded."));
        };

        oldScript.replaceWith(script);
    });
}

async function loadPageScripts(body, baseUrl) {
    const scripts = Array.from(body.querySelectorAll("script[src]"));

    for (const script of scripts) {
        await loadExternalScript(script, baseUrl);
    }
}

function updateDocumentMetadata(newDoc) {
    if (newDoc.title) {
        document.title = newDoc.title;
    }

    const newCsrfToken = newDoc.querySelector('meta[name="csrf-token"]');
    const currentCsrfToken = document.querySelector('meta[name="csrf-token"]');

    if (newCsrfToken && currentCsrfToken) {
        currentCsrfToken.content = newCsrfToken.content;
    }

    const newDescription = newDoc.querySelector('meta[name="description"]');
    let currentDescription = document.querySelector('meta[name="description"]');

    if (newDescription) {
        if (!currentDescription) {
            currentDescription = document.createElement("meta");
            currentDescription.name = "description";
            document.head.append(currentDescription);
        }

        currentDescription.content = newDescription.content;
    }

    document.documentElement.lang = newDoc.documentElement.lang || document.documentElement.lang;
}

function saveCurrentScrollPosition() {
    const state = {
        ...(history.state && typeof history.state === "object" ? history.state : {}),
        __boostScrollY: window.scrollY,
    };

    history.replaceState(state, "", window.location.href);
}

function updateHistory(url, mode) {
    const state = {
        __boost: true,
        __boostScrollY: 0,
    };

    if (mode === "replace") {
        history.replaceState(state, "", url);
    } else {
        history.pushState(state, "", url);
    }
}

function scrollToDestination(url, savedScrollY) {
    if (typeof savedScrollY === "number") {
        window.scrollTo({ top: savedScrollY, left: 0, behavior: "auto" });
        return;
    }

    const destination = new URL(url);
    const hash = destination.hash;

    if (hash) {
        let id = hash.slice(1);

        try {
            id = decodeURIComponent(id);
        } catch {
            // Use the raw fragment when it contains invalid URI encoding.
        }

        document.getElementById(id)?.scrollIntoView();
        return;
    }

    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
}

async function loadContent(url, options = {}) {
    const currentNavigation = ++navigationId;

    NProgress.start();

    try {
        const response = await fetch(url, options);

        if (!isSameOrigin(response.url || url)) {
            return { success: false };
        }

        const contentType = response.headers.get("content-type") || "";

        if (!contentType.toLowerCase().includes("text/html")) {
            return { success: false };
        }

        const html = await response.text();

        if (currentNavigation !== navigationId) {
            return { success: false, stale: true };
        }

        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, "text/html");

        if (!newDoc.body || !newDoc.documentElement) {
            return { success: false };
        }

        sanitizeDocument(newDoc);

        const destination = new URL(response.url || url);
        const requestedHash = new URL(url, window.location.href).hash;

        // Fetch omits URL fragments from the network request and response URL.
        if (!destination.hash && requestedHash) {
            destination.hash = requestedHash;
        }

        dispatch("boost:before-swap", {
            url: destination.href,
            response,
        });

        updateDocumentMetadata(newDoc);

        const newBody = newDoc.body;
        document.body.replaceWith(newBody);

        await loadPageScripts(document.body, destination.href);

        if (currentNavigation !== navigationId) {
            return { success: false, stale: true };
        }

        dispatch("boost:page-ready", {
            url: destination.href,
            response,
            status: response.status,
        });

        return {
            success: true,
            url: destination.href,
            status: response.status,
        };
    } catch (error) {
        console.error("[Boost] Navigation failed:", error);
        dispatch("boost:error", { error, url });
        return { success: false, error };
    } finally {
        if (currentNavigation === navigationId) {
            NProgress.done();
        }
    }
}

function createNavigationOptions() {
    return {
        method: "GET",
        headers: { ...REQUEST_HEADERS },
        credentials: "same-origin",
        mode: "same-origin",
        redirect: "follow",
        cache: "no-store",
        referrerPolicy: "same-origin",
    };
}

async function navigateTo(url, options = createNavigationOptions(), historyMode = "push") {
    const result = await loadContent(url, options);

    if (!result.success) {
        if (!result.stale) {
            window.location.assign(url);
        }

        return false;
    }

    if (historyMode !== "none") {
        updateHistory(
            result.url,
            historyMode === "replace" ? "replace" : "push",
        );
    }

    scrollToDestination(result.url);
    return true;
}

function handleLinkClick(event) {
    const target = event.target;

    if (!(target instanceof Element)) return;

    const anchor = target.closest("a[href]");

    if (!anchor || !shouldBoostLink(anchor, event)) return;

    event.preventDefault();
    saveCurrentScrollPosition();
    navigateTo(anchor.href);
}

async function handleFormSubmit(event) {
    const form = event.target;

    if (
        !(form instanceof HTMLFormElement) ||
        !shouldBoostForm(form, event) ||
        submittingForms.has(form)
    ) {
        return;
    }

    event.preventDefault();

    const submitter = event.submitter;

    let request;

    try {
        request = buildFormRequest(form, submitter);
    } catch (error) {
        console.error("[Boost] Unable to prepare form:", error);
        form.submit();
        return;
    }

    submittingForms.add(form);
    form.setAttribute("aria-busy", "true");
    saveCurrentScrollPosition();

    try {
        const result = await loadContent(request.url, request.options);

        if (!result.success) {
            if (!result.stale) {
                dispatch("boost:form-error", {
                    form,
                    error: result.error,
                    status: result.status,
                });

                // Do not automatically repeat a failed mutation request.
                // The server may already have processed it.
                if (request.options.method === "GET") {
                    window.location.assign(request.url);
                } else {
                    window.location.reload();
                }
            }

            return;
        }

        const currentUrl = new URL(window.location.href);
        const responseUrl = new URL(result.url);

        if (currentUrl.href !== responseUrl.href) {
            updateHistory(result.url, "push");
        } else {
            updateHistory(result.url, "replace");
        }

        scrollToDestination(result.url);
    } finally {
        submittingForms.delete(form);
        form.removeAttribute("aria-busy");
    }
}

async function handlePopState(event) {
    const result = await loadContent(window.location.href, {
        method: "GET",
        headers: { ...REQUEST_HEADERS },
        credentials: "same-origin",
        mode: "same-origin",
        redirect: "follow",
        cache: "no-store",
        referrerPolicy: "same-origin",
    });

    if (result.success) {
        scrollToDestination(
            result.url,
            event.state?.__boostScrollY,
        );
    } else if (!result.stale) {
        window.location.reload();
    }
}

function initializeBoost() {
    rememberExistingScripts();

    if (!history.state || typeof history.state !== "object") {
        history.replaceState({ __boost: true, __boostScrollY: window.scrollY }, "", window.location.href);
    }

    document.addEventListener("click", handleLinkClick);
    document.addEventListener("submit", handleFormSubmit);
    window.addEventListener("popstate", handlePopState);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeBoost, { once: true });
} else {
    initializeBoost();
}

const IMAGE_TYPES = new Set(["image/png", "image/jpeg", "image/gif", "image/webp"]);

function formatFileSize(bytes) {
    if (!bytes) return "0 B";
    const units = ["B", "KB", "MB", "GB"];
    const unitIndex = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${parseFloat((bytes / 1024 ** unitIndex).toFixed(1))} ${units[unitIndex]}`;
}

function initFileUpload(container) {
    if (container.dataset.initialized === "true") return;
    container.dataset.initialized = "true";

    const fileInput = container.querySelector("[data-file-upload-input-file]");
    const dropzone = container.querySelector("[data-file-upload-dropzone]");
    const preview = container.querySelector("[data-file-upload-preview]");
    const hiddenInput = container.querySelector("[data-file-upload-hidden]");
    const template = container.querySelector("[data-file-upload-template]");
    const status = container.querySelector("[data-file-upload-status]");
    const error = container.querySelector("[data-file-upload-error]");
    if (!fileInput || !dropzone || !preview || !template) return;

    const mode = container.dataset.uploadMode || "deferred";
    const maxSize = Number(container.dataset.maxSize || 2097152);
    const imageMaxSize = Number(container.dataset.imageMaxSize || maxSize);
    const videoMaxSize = Number(container.dataset.videoMaxSize || maxSize);
    const multiple = container.dataset.multiple === "true";
    const acceptedFormats = (fileInput.accept || "").split(",").map((item) => item.trim().toLowerCase()).filter(Boolean);
    const csrfToken = document.querySelector("meta[name='csrf-token']")?.content;
    const uploadedFiles = [];
    let fallbackValue = hiddenInput?.value ?? "";
    let objectUrls = [];

    const showError = (message) => {
        if (!error) return;
        error.textContent = message;
        error.classList.remove("hidden");
    };
    const clearError = () => {
        if (!error) return;
        error.textContent = "";
        error.classList.add("hidden");
    };
    const showStatus = (message) => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle("hidden", !message);
    };
    const syncHiddenInput = () => {
        if (hiddenInput) hiddenInput.value = uploadedFiles[0]?.url ?? fallbackValue;
    };

    async function uploadFile(file) {
        const formData = new FormData();
        formData.append("file", file);
        const response = await fetch(container.dataset.uploadUrl, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, Accept: "application/json" },
            body: formData,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(data.errors?.file?.[0] ?? data.message ?? "File upload failed.");
        return data;
    }

    async function deleteUploadedFile(item, button) {
        button.disabled = true;
        try {
            const response = await fetch(container.dataset.deleteUrl, {
                method: "DELETE",
                headers: { "X-CSRF-TOKEN": csrfToken, "Content-Type": "application/json", Accept: "application/json" },
                body: JSON.stringify({ path: item.path }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(data.message ?? "Failed to remove uploaded file.");
            item.card.remove();
            const index = uploadedFiles.indexOf(item);
            if (index !== -1) uploadedFiles.splice(index, 1);
            syncHiddenInput();
            clearError();
        } catch (exception) {
            showError(exception.message);
            button.disabled = false;
        }
    }

    function addPreview(file, data = null) {
        const fragment = template.content.cloneNode(true);
        const card = fragment.querySelector("[data-file-preview-card]");
        const image = card.querySelector("[data-file-preview-image]");
        const fileLabel = card.querySelector("[data-file-preview-file]");
        const name = card.querySelector("[data-file-name]");
        const meta = card.querySelector("[data-file-meta]");
        const deleteButton = card.querySelector("[data-file-delete]");
        const extension = (data?.extension || file.name.split(".").pop() || "FILE").toUpperCase();
        const isImage = IMAGE_TYPES.has(file.type);
        const objectUrl = data?.url || URL.createObjectURL(file);
        if (!data) objectUrls.push(objectUrl);

        if (isImage) {
            image.src = objectUrl;
            image.alt = file.name;
            image.classList.remove("hidden");
        } else {
            fileLabel.textContent = extension;
            fileLabel.classList.remove("hidden");
        }
        name.textContent = file.name;
        meta.textContent = `${extension} · ${formatFileSize(data?.size ?? file.size)}`;

        const item = { path: data?.path, url: data?.url, card, objectUrl };
        deleteButton.addEventListener("click", () => {
            if (mode === "async" && item.path) {
                deleteUploadedFile(item, deleteButton);
                return;
            }
            card.remove();
            if (item.objectUrl?.startsWith("blob:")) URL.revokeObjectURL(item.objectUrl);
            objectUrls = objectUrls.filter((url) => url !== item.objectUrl);
            if (mode === "deferred") fileInput.value = "";
            const index = uploadedFiles.indexOf(item);
            if (index !== -1) uploadedFiles.splice(index, 1);
            syncHiddenInput();
        });
        preview.append(card);
        uploadedFiles.push(item);
        syncHiddenInput();
        return item;
    }

    function removeExistingFile() {
        const existingCard = preview.querySelector("[data-file-existing]");
        if (!existingCard) return;
        existingCard.remove();
        fallbackValue = "";
        syncHiddenInput();
    }

    async function handleFiles(fileList) {
        const selectedFiles = Array.from(fileList ?? []);
        clearError();
        if (!multiple) {
            preview.querySelectorAll("[data-file-preview-card]:not([data-file-existing])").forEach((card) => card.remove());
            uploadedFiles.splice(0).forEach((item) => {
                if (item.objectUrl?.startsWith("blob:")) URL.revokeObjectURL(item.objectUrl);
            });
            if (mode === "async") {
                const existing = preview.querySelector("[data-file-existing]");
                if (existing) removeExistingFile();
            }
        }

        const validFiles = selectedFiles.filter((file) => {
            const extension = `.${file.name.split(".").pop().toLowerCase()}`;
            const accepted = !acceptedFormats.length || acceptedFormats.some((format) =>
                format === file.type.toLowerCase() || format === extension || (format.endsWith("/*") && file.type.startsWith(format.slice(0, -1)))
            );
            if (!accepted) {
                showError(`${file.name} menggunakan format file yang tidak didukung.`);
                return false;
            }
            const fileMaxSize = file.type.startsWith("video/") ? videoMaxSize : file.type.startsWith("image/") ? imageMaxSize : maxSize;
            if (file.size > fileMaxSize) {
                showError(`${file.name} melebihi batas ${formatFileSize(fileMaxSize)}.`);
                return false;
            }
            if (file.type.startsWith("image/") && !IMAGE_TYPES.has(file.type)) {
                showError(`${file.name} menggunakan format gambar yang tidak didukung.`);
                return false;
            }
            return true;
        });
        if (!validFiles.length) return;

        if (mode === "deferred") {
            validFiles.forEach((file) => addPreview(file));
            return;
        }

        showStatus(`Uploading ${validFiles.length} file${validFiles.length > 1 ? "s" : ""}...`);
        await Promise.all(validFiles.map(async (file) => {
            try {
                const localPreview = addPreview(file);
                const data = await uploadFile(file);
                localPreview.card.querySelector("[data-file-preview-image]").src = data.url;
                localPreview.url = data.url;
                localPreview.path = data.path;
                if (localPreview.objectUrl?.startsWith("blob:")) URL.revokeObjectURL(localPreview.objectUrl);
                localPreview.objectUrl = null;
            } catch (exception) {
                showError(exception.message);
            }
        }));
        showStatus("");
        fileInput.value = "";
    }

    preview.querySelector("[data-file-delete-existing]")?.addEventListener("click", removeExistingFile);
    fileInput.addEventListener("change", () => handleFiles(fileInput.files));
    ["dragenter", "dragover"].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.add("ring-2", "ring-indigo-400");
    }));
    ["dragleave", "drop"].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        dropzone.classList.remove("ring-2", "ring-indigo-400");
    }));
    dropzone.addEventListener("drop", (event) => {
        const files = event.dataTransfer.files;
        if (mode === "deferred") {
            const transfer = new DataTransfer();
            Array.from(files).forEach((file) => transfer.items.add(file));
            fileInput.files = transfer.files;
        }
        handleFiles(files);
    });
}

function initializeFileUploads() {
    document.querySelectorAll("[data-file-upload]").forEach(initFileUpload);
}

document.addEventListener("boost:page-ready", initializeFileUploads);
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeFileUploads, { once: true });
} else {
    initializeFileUploads();
}

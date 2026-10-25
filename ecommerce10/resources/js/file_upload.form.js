const MAX_FILE_SIZE = 2 * 1024 * 1024;
const ACCEPTED_TYPES = new Set([
    "image/png",
    "image/jpeg",
    "image/gif",
    "image/webp",
]);

function formatFileSize(bytes) {
    if (!bytes) return "0 B";

    const units = ["B", "KB", "MB", "GB"];
    const unitIndex = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const value = bytes / 1024 ** unitIndex;

    return `${parseFloat(value.toFixed(1))} ${units[unitIndex]}`;
}

function initFileUpload(container) {
    if (container.dataset.initialized) return;
    container.dataset.initialized = "true";

    const fileInput = container.querySelector("input[type='file']");
    const dropzone = container.querySelector("[data-file-upload-dropzone]");
    const preview = container.querySelector("[data-file-upload-preview]");
    const hiddenInput = container.querySelector("[data-file-upload-input]");
    const template = container.querySelector("[data-file-upload-template]");
    const status = container.querySelector("[data-file-upload-status]");
    const error = container.querySelector("[data-file-upload-error]");

    if (!fileInput || !dropzone || !preview || !template) return;

    const csrfToken = document.querySelector("meta[name='csrf-token']")?.content;
    const uploadUrl = container.dataset.uploadUrl;
    const deleteUrl = container.dataset.deleteUrl;
    const existingCard = preview.querySelector("[data-file-existing]");
    const existingDeleteButton = existingCard?.querySelector("[data-file-delete-existing]");
    let fallbackImage = hiddenInput?.value ?? "";
    const uploadedFiles = [];

    function showError(message) {
        if (!error) return;
        error.textContent = message;
        error.classList.remove("hidden");
    }

    function clearError() {
        if (!error) return;
        error.textContent = "";
        error.classList.add("hidden");
    }

    function showStatus(message) {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle("hidden", !message);
    }

    function syncHiddenInput() {
        if (!hiddenInput) return;
        hiddenInput.value = uploadedFiles[0]?.url ?? fallbackImage;
    }

    async function uploadFile(file) {
        const formData = new FormData();
        formData.append("file", file);

        const response = await fetch(uploadUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: formData,
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                data.errors?.file?.[0] ?? data.message ?? "Failed to upload the image.",
            );
        }

        return data;
    }

    async function deleteFile(item, button) {

        if(!confirm('Are you sure?')) return;

        button.disabled = true;
        button.classList.add("opacity-50");

        try {
            const response = await fetch(deleteUrl, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify({ path: item.path }),
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(data.message ?? "Failed to delete the image.");
            }

            item.card.remove();

            const index = uploadedFiles.indexOf(item);
            if (index !== -1) uploadedFiles.splice(index, 1);

            syncHiddenInput();
            clearError();
        } catch (deleteError) {
            showError(deleteError.message);
            button.disabled = false;
            button.classList.remove("opacity-50");
        }
    }

    function removeExistingImage() {
        if (!existingCard) return;

        existingCard.remove();
        fallbackImage = "";
        syncHiddenInput();
        clearError();
    }

    function addPreview(data) {
        const fragment = template.content.cloneNode(true);
        const card = fragment.querySelector("[data-file-preview-card]");
        const image = card.querySelector("[data-file-preview]");
        const name = card.querySelector("[data-file-name]");
        const meta = card.querySelector("[data-file-meta]");
        const deleteButton = card.querySelector("[data-file-delete]");

        const extension = (data.extension || data.name.split(".").pop() || "file").toUpperCase();
        const item = {
            path: data.path,
            url: data.url,
            card,
        };

        image.src = data.url;
        image.alt = data.name;
        name.textContent = data.name;
        meta.textContent = `${extension} · ${formatFileSize(data.size)}`;

        deleteButton.addEventListener("click", function () {
            deleteFile(item, deleteButton);
        });

        preview.append(card);
        uploadedFiles.push(item);
        syncHiddenInput();
    }

    async function handleFiles(files) {
        const selectedFiles = Array.from(files);
        const images = [];

        clearError();

        selectedFiles.forEach(function (file) {
            if (!ACCEPTED_TYPES.has(file.type)) {
                showError(`${file.name} is not a supported image.`);
                return;
            }

            if (file.size > MAX_FILE_SIZE) {
                showError(`${file.name} is larger than 2 MB.`);
                return;
            }

            images.push(file);
        });

        if (!images.length) return;

        showStatus(`Uploading ${images.length} file${images.length > 1 ? "s" : ""}...`);

        await Promise.all(
            images.map(async function (file) {
                try {
                    addPreview(await uploadFile(file));
                } catch (uploadError) {
                    showError(uploadError.message);
                }
            }),
        );

        showStatus("");
    }

    existingDeleteButton?.addEventListener("click", removeExistingImage);

    fileInput.addEventListener("change", function () {
        const files = Array.from(fileInput.files ?? []);
        fileInput.value = "";

        if (files.length) {
            handleFiles(files);
        }
    });

    ["dragenter", "dragover"].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.add("ring-2", "ring-indigo-400");
        });
    });

    ["dragleave", "drop"].forEach(function (eventName) {
        dropzone.addEventListener(eventName, function (event) {
            event.preventDefault();
            dropzone.classList.remove("ring-2", "ring-indigo-400");
        });
    });

    dropzone.addEventListener("drop", function (event) {
        handleFiles(event.dataTransfer.files);
    });
}

document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[data-file-upload]").forEach(initFileUpload);
});

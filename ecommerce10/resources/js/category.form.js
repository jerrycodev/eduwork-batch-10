import Pristine from "pristinejs";
import slugify from "slugify";

/** Inisialisasi dialog kategori dan validasi client-side; guard mencegah listener ganda saat Boost navigasi. */
function initializeCategoryForm() {
    const form = document.querySelector("#category-form");

    if (!form || form.dataset.boostInitialized) return;
    form.dataset.boostInitialized = "true";

    const dialog = document.querySelector("#category-dialog");
    const createButton = document.querySelector("#category-dialog-button");
    const editButtons = document.querySelectorAll("[data-dialog-mode='edit']");

    const inputName = form.querySelector("#category-name");
    const inputSlug = form.querySelector("#category-slug");
    const inputId = form.querySelector("#category-id");
    const inputMethod = form.querySelector("input[name='_method']");
    const inputImage = form.querySelector("#category-image");
    const previewWrap = form.querySelector("#category-image-preview-wrap");
    const preview = form.querySelector("#category-image-preview");
    const previewLabel = form.querySelector("#category-image-preview-label");

    const title = form.querySelector("#category-form-title");
    const description = form.querySelector("#category-form-description");
    const submitButton = form.querySelector("#category-form-submit");
    const storeUrl = form.action;

    Pristine.addMessages("id", {
        required: "Bagian ini wajib diisi.",
        email: "Masukkan alamat email yang valid.",
        number: "Masukkan angka yang valid.",
        integer: "Masukkan bilangan bulat.",
        url: "Masukkan URL yang valid.",
        tel: "Masukkan nomor telepon yang valid.",
        maxlength: "Panjang teks maksimal ${1} karakter.",
        minlength: "Panjang teks minimal ${1} karakter.",
        min: "Nilai minimal adalah ${1}.",
        max: "Nilai maksimal adalah ${1}.",
        pattern: "Format yang dimasukkan belum sesuai.",
        equals: "Nilai kedua kolom harus sama.",
        default: "Masukkan nilai yang valid.",
    });
    Pristine.setLocale("id");

    const pristine = new Pristine(form, {
        classTo: "form-field",
        errorClass: "has-error",
        successClass: "has-success",
        errorTextParent: "form-field",
        errorTextTag: "p",
        errorTextClass: "mt-1 text-sm text-red-500",
    });

    // Menampilkan gambar kategori saat ini atau preview file yang baru dipilih.
    function setPreview(url, label = "Image preview") {
        if (!url) {
            preview.removeAttribute("src");
            previewWrap.classList.add("hidden");
            previewWrap.classList.remove("flex");
            return;
        }

        preview.src = url;
        previewLabel.textContent = label;
        previewWrap.classList.remove("hidden");
        previewWrap.classList.add("flex");
    }

    // Mengisi ulang form sesuai mode create/edit agar data kategori tidak tertukar antar dialog.
    function configureForm({ mode, id = "", name = "", slug = "", url = "", image = "" }) {
        pristine.reset();
        form.reset();

        const isEdit = mode === "edit";
        form.action = isEdit ? url : storeUrl;
        inputMethod.value = isEdit ? "PUT" : "POST";
        inputId.value = id;
        inputName.value = name;
        inputSlug.value = slug;
        setPreview(image, image ? "Current category image — upload a new file to replace it" : "");

        title.textContent = isEdit ? "Edit Category" : "New Category";
        description.textContent = isEdit ? "Update this category" : "Create a new category";
        submitButton.textContent = isEdit ? "Update Category" : "Save changes";
    }

    // Membuat slug dari nama kategori dan menjalankan ulang validasi field slug.
    function setSlug(value) {
        if (!value) return;
        inputSlug.value = slugify(value, { lower: true });
        pristine.validate(inputSlug);
    }

    inputName.addEventListener("blur", () => setSlug(inputName.value));
    inputSlug.addEventListener("blur", () => setSlug(inputSlug.value));

    inputImage.addEventListener("change", () => {
        const file = inputImage.files?.[0];
        if (!file) return;
        if (!file.type.startsWith("image/")) return;

        const objectUrl = URL.createObjectURL(file);
        setPreview(objectUrl, file.name);
        preview.dataset.objectUrl = objectUrl;
    });

    createButton?.addEventListener("click", () => {
        configureForm({ mode: "create" });
        if (dialog && !dialog.open) dialog.showModal();
    });

    dialog?.querySelectorAll("[data-dialog-close]").forEach((button) => {
        button.addEventListener("click", () => dialog.close());
    });

    dialog?.addEventListener("click", (event) => {
        if (event.target === dialog) dialog.close();
    });

    editButtons.forEach((button) => {
        button.addEventListener("click", () => {
            configureForm({
                mode: "edit",
                id: button.dataset.dialogId,
                name: button.dataset.dialogName,
                slug: button.dataset.dialogSlug,
                url: button.dataset.dialogUrl,
                image: button.dataset.dialogImage,
            });
            if (dialog && !dialog.open) dialog.showModal();
        });
    });

    form.addEventListener("submit", (event) => {
        if (!pristine.validate()) event.preventDefault();
    });

    dialog?.addEventListener("close", () => {
        const objectUrl = preview.dataset.objectUrl;
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        delete preview.dataset.objectUrl;
        setTimeout(() => configureForm({ mode: "create" }), 150);
    });
}

document.addEventListener("boost:page-ready", initializeCategoryForm);

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeCategoryForm, { once: true });
} else {
    initializeCategoryForm();
}

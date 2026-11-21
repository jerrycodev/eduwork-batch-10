import Pristine from "pristinejs";
import slugify from "slugify";

/** Validasi form produk, pembuatan slug, dan pemeriksaan minimal satu gambar. */
function initializeProductForm() {
    const form = document.querySelector("#product-form");

    if (!form) return;
    if (form.dataset.boostInitialized) return;
    form.dataset.boostInitialized = "true";

    const input_name = form.querySelector("[name='name']");
    const input_slug = form.querySelector("[name='slug']");
    const input_image = form.querySelector("[name='image']");
    const productImages = form.querySelector("#product-images");
    const imageError = form.querySelector("[data-file-upload-error]");

    Pristine.addMessages("id", {
        required: "Bagian ini wajib diisi.",
        email: "Masukkan alamat email yang valid.",
        number: "Masukkan angka yang valid.",
        integer: "Masukkan bilangan bulat.",
        url: "Masukkan URL yang valid.",
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

    // Slug dibuat otomatis dari nama produk ketika field kehilangan fokus.
    function setSlug(value) {
        if (!value) return;

        input_slug.value = slugify(value, {
            lower: true,
        });

        pristine.validate(input_slug);
    }

    // Menampilkan pesan jika produk baru belum memiliki gambar.
    function showImageError(message) {
        if (!imageError) return;

        imageError.textContent = message;
        imageError.classList.remove("hidden");
    }

    function clearImageError() {
        if (!imageError) return;

        imageError.textContent = "";
        imageError.classList.add("hidden");
    }

    input_name.addEventListener("blur", function () {
        setSlug(input_name.value);
    });

    input_slug.addEventListener("blur", function () {
        setSlug(input_slug.value);
    });

    input_image?.addEventListener("change", clearImageError);
    productImages?.addEventListener("change", clearImageError);

    form.addEventListener("submit", function (event) {
        let isValid = pristine.validate();

        clearImageError();

        // New products can use the gallery upload; the legacy hidden image field is
        // populated only when an existing/legacy image is already available.
        if (!input_image?.value && !(productImages?.files?.length > 0)) {
            showImageError("Upload minimal satu gambar produk.");
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    form.addEventListener("reset", function () {
        pristine.reset();
        clearImageError();
    });
}

document.addEventListener("boost:page-ready", initializeProductForm);

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initializeProductForm, { once: true });
} else {
    initializeProductForm();
}

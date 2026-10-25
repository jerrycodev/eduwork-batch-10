import Pristine from "pristinejs";
import slugify from "slugify";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#product-form");

    if (!form) return;

    const input_name = form.querySelector("[name='name']");
    const input_slug = form.querySelector("[name='slug']");
    const input_image = form.querySelector("[name='image']");
    const imageError = form.querySelector("[data-file-upload-error]");

    const pristine = new Pristine(form, {
        classTo: "form-field",
        errorClass: "has-error",
        successClass: "has-success",
        errorTextParent: "form-field",
        errorTextTag: "p",
        errorTextClass: "mt-1 text-sm text-red-500",
    });

    function setSlug(value) {
        if (!value) return;

        input_slug.value = slugify(value, {
            lower: true,
        });

        pristine.validate(input_slug);
    }

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
    form.querySelector("input[type='file']")?.addEventListener("change", clearImageError);

    form.addEventListener("submit", function (event) {
        let isValid = pristine.validate();

        clearImageError();

        if (!input_image?.value) {
            showImageError("Please upload an image.");
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
});

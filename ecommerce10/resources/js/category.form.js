import Pristine from "pristinejs";
import slugify from "slugify";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#category-form");

    if (!form) return;

    const dialog = document.querySelector("#category-dialog");
    const createButton = document.querySelector("#category-dialog-button");
    const editButtons = document.querySelectorAll("[data-dialog-mode='edit']");

    const input_name = form.querySelector("#category-name");
    const input_slug = form.querySelector("#category-slug");
    const input_id = form.querySelector("#category-id");
    const input_method = form.querySelector("input[name='_method']");

    const title = form.querySelector("#category-form-title");
    const description = form.querySelector("#category-form-description");
    const submitButton = form.querySelector("#category-form-submit");

    const storeUrl = form.action;

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

    function configureForm({ mode, id = "", name = "", slug = "", url = "" }) {
        pristine.reset();
        form.reset();

        const isEdit = mode === "edit";

        form.action = isEdit ? url : storeUrl;
        input_method.value = isEdit ? "PUT" : "POST";
        input_id.value = id;
        input_name.value = name;
        input_slug.value = slug;

        title.textContent = isEdit ? "Edit Category" : "New Category";
        description.textContent = isEdit
            ? "Update this category"
            : "Create a new category";
        submitButton.textContent = isEdit ? "Update Category" : "Save changes";
    }

    input_name.addEventListener("blur", function () {
        setSlug(input_name.value);
    });

    input_slug.addEventListener("blur", function () {
        setSlug(input_slug.value);
    });

    createButton?.addEventListener("click", function () {
        configureForm({ mode: "create" });
    });

    editButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            configureForm({
                mode: "edit",
                id: button.dataset.dialogId,
                name: button.dataset.dialogName,
                slug: button.dataset.dialogSlug,
                url: button.dataset.dialogUrl,
            });
        });
    });

    form.addEventListener("submit", function (event) {
        if (!pristine.validate()) {
            event.preventDefault();
        }
    });

    dialog?.addEventListener("close", function () {
        setTimeout(function () {
            configureForm({ mode: "create" });
        }, 500);
    });
});

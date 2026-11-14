import NProgress from "nprogress";
import "nprogress/nprogress.css";

// Optional: Configure NProgress (e.g., disable the spinning circle)
NProgress.configure({ showSpinner: false });

// Make it globally available
window.NProgress = NProgress;

// Start the progress bar when clicking links or submitting forms
window.addEventListener("beforeunload", () => {
    NProgress.start();
});

// Finish the progress bar once the page finishes loading
window.addEventListener("load", () => {
    NProgress.done();
});

// Handle browser back/forward cache (bfcache)
window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
        NProgress.done();
    }
});

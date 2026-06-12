import chromium from "@sparticuz/chromium";

(async () => {
    const path = await chromium.executablePath();
    console.log(`${path}`);
})();

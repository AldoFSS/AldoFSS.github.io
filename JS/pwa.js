(() => {
  const installButton = document.getElementById("pwa-install");
  const status = document.getElementById("pwa-status");
  let installPrompt;
  let waitingWorker;
  let refreshing = false;

  if (!installButton || !status) {
    return;
  }

  const updateConnectionStatus = () => {
    const offline = !navigator.onLine;
    status.textContent = offline ? "Sin conexión" : "";
    status.classList.toggle("is-offline", offline);
  };

  updateConnectionStatus();
  window.addEventListener("online", updateConnectionStatus);
  window.addEventListener("offline", updateConnectionStatus);

  window.addEventListener("beforeinstallprompt", (event) => {
    event.preventDefault();
    installPrompt = event;
    installButton.hidden = false;
    installButton.textContent = "Instalar app";
  });

  window.addEventListener("appinstalled", () => {
    installPrompt = null;
    waitingWorker = null;
    installButton.hidden = true;
    status.textContent = "Cafeliz quedó instalada";
    window.setTimeout(updateConnectionStatus, 4000);
  });

  installButton.addEventListener("click", async () => {
    if (waitingWorker) {
      waitingWorker.postMessage({ type: "SKIP_WAITING" });
      return;
    }

    if (!installPrompt) {
      return;
    }

    installPrompt.prompt();
    await installPrompt.userChoice;
    installPrompt = null;
    installButton.hidden = true;
  });

  if (!("serviceWorker" in navigator)) {
    return;
  }

  window.addEventListener("load", async () => {
    try {
      const registration = await navigator.serviceWorker.register("./service-worker.js");

      const offerUpdate = (worker) => {
        waitingWorker = worker;
        installButton.hidden = false;
        installButton.textContent = "Actualizar app";
      };

      if (registration.waiting) {
        offerUpdate(registration.waiting);
      }

      registration.addEventListener("updatefound", () => {
        const installingWorker = registration.installing;
        if (!installingWorker) {
          return;
        }

        installingWorker.addEventListener("statechange", () => {
          if (installingWorker.state === "installed" && navigator.serviceWorker.controller) {
            offerUpdate(installingWorker);
          }
        });
      });

      navigator.serviceWorker.addEventListener("controllerchange", () => {
        if (!refreshing) {
          refreshing = true;
          window.location.reload();
        }
      });
    } catch (error) {
      console.error("No se pudo registrar el service worker de Cafeliz.", error);
    }
  });
})();

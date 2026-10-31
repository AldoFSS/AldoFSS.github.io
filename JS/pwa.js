(() => {
  const updateButton = document.getElementById("pwa-update");
  const notificationsButton = document.getElementById("pwa-notifications");
  const status = document.getElementById("pwa-status");
  const feedback = document.getElementById("pwa-feedback");
  let waitingWorker;
  let refreshing = false;

  const updateConnectionStatus = () => {
    const offline = !navigator.onLine;
    if (status) {
      status.textContent = offline ? "Sin conexión" : "";
      status.classList.toggle("is-offline", offline);
    }

    if (document.body?.dataset.offlineView !== "true") return;
    document.body.classList.toggle("offline-read-only", offline);
    let banner = document.getElementById("offline-read-only-banner");
    if (offline && !banner) {
      banner = document.createElement("p");
      banner.id = "offline-read-only-banner";
      banner.className = "pwa-offline-banner";
      banner.setAttribute("role", "status");
      banner.textContent = "Sin conexión: estás viendo la última versión guardada. Las acciones están desactivadas.";
      document.querySelector("main")?.prepend(banner);
    } else if (!offline && banner) {
      banner.remove();
    }

    document.querySelectorAll("main button, main input, main select, main textarea").forEach((control) => {
      if (offline) {
        if (!control.hasAttribute("data-offline-original-disabled")) {
          control.dataset.offlineOriginalDisabled = String(control.disabled);
        }
        control.disabled = true;
      } else if (control.hasAttribute("data-offline-original-disabled")) {
        control.disabled = control.dataset.offlineOriginalDisabled === "true";
        delete control.dataset.offlineOriginalDisabled;
      }
    });
  };

  document.addEventListener("click", (event) => {
    if (navigator.onLine || document.body?.dataset.offlineView !== "true") return;
    if (event.target.closest("main button, main input, main select, main textarea")) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);
  document.addEventListener("submit", (event) => {
    if (navigator.onLine || document.body?.dataset.offlineView !== "true") return;
    if (event.target.closest("main form")) {
      event.preventDefault();
      event.stopImmediatePropagation();
    }
  }, true);

  updateConnectionStatus();
  window.addEventListener("online", updateConnectionStatus);
  window.addEventListener("offline", updateConnectionStatus);

  window.addEventListener("appinstalled", () => {
    waitingWorker = null;
    if (feedback) feedback.textContent = "CaféLiz quedó instalada.";
    window.setTimeout(() => {
      if (feedback) feedback.textContent = "";
      updateConnectionStatus();
    }, 4000);
  });

  const base64UrlToBytes = (value) => {
    const padding = "=".repeat((4 - value.length % 4) % 4);
    const base64 = (value + padding).replace(/-/g, "+").replace(/_/g, "/");
    const raw = window.atob(base64);
    return Uint8Array.from(raw, (character) => character.charCodeAt(0));
  };

  const postSubscription = async (subscription, action) => {
    const response = await fetch("PHP/PushNotifications.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json", Accept: "application/json" },
      body: JSON.stringify({ action, subscription: subscription.toJSON() })
    });
    const result = await response.json().catch(() => null);
    if (!result) throw new Error("El servidor devolvió una respuesta no válida.");
    if (!response.ok || !result.success) {
      throw new Error(result.message || "No se pudo actualizar la suscripción de notificaciones.");
    }
    return result;
  };

  const configurePushButton = async (registration) => {
    if (!notificationsButton) return;
    if (!("PushManager" in window) || !("Notification" in window)) {
      notificationsButton.hidden = true;
      if (feedback) feedback.textContent = "Este navegador no admite notificaciones push.";
      return;
    }
    const appleMobile = /iphone|ipad|ipod/i.test(navigator.userAgent) ||
      (navigator.platform === "MacIntel" && navigator.maxTouchPoints > 1);
    if (appleMobile && !navigator.standalone &&
        !window.matchMedia("(display-mode: standalone)").matches) {
      notificationsButton.hidden = true;
      if (feedback) feedback.textContent = "En iPhone o iPad, instala CaféLiz en la pantalla de inicio antes de activar notificaciones.";
      return;
    }
    notificationsButton.hidden = false;
    let subscription;
    let publicKey;
    try {
      subscription = await registration.pushManager.getSubscription();
      notificationsButton.textContent = subscription
        ? "Desactivar notificaciones"
        : "Activar notificaciones";
      if (!subscription) {
        const keyResponse = await fetch("PHP/PushNotifications.php?accion=clave", {
          credentials: "same-origin",
          headers: { Accept: "application/json" }
        });
        const keyResult = await keyResponse.json().catch(() => null);
        if (!keyResult || !keyResponse.ok || !keyResult.success || !keyResult.publicKey) {
          throw new Error(keyResult?.message || "Las notificaciones no están configuradas en el servidor.");
        }
        publicKey = keyResult.publicKey;
      }
    } catch (error) {
      notificationsButton.hidden = true;
      if (feedback) feedback.textContent = error.message;
      console.error("No se pudo preparar la suscripción push.", error);
      return;
    }

    notificationsButton.addEventListener("click", async () => {
      notificationsButton.disabled = true;
      try {
        if (subscription) {
          await postSubscription(subscription, "unsubscribe");
          await subscription.unsubscribe();
          subscription = null;
          notificationsButton.textContent = "Activar notificaciones";
          if (feedback) feedback.textContent = "Notificaciones desactivadas en este dispositivo.";
          return;
        }

        const permission = await Notification.requestPermission();
        if (permission !== "granted") {
          throw new Error("No se concedió permiso para mostrar notificaciones.");
        }
        const newSubscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: base64UrlToBytes(publicKey)
        });
        try {
          await postSubscription(newSubscription, "subscribe");
        } catch (error) {
          await newSubscription.unsubscribe();
          throw error;
        }
        subscription = newSubscription;
        notificationsButton.textContent = "Desactivar notificaciones";
        if (feedback) feedback.textContent = "Notificaciones activadas para esta cuenta y dispositivo.";
      } catch (error) {
        if (feedback) feedback.textContent = error.message;
      } finally {
        notificationsButton.disabled = false;
      }
    });
  };

  if ("serviceWorker" in navigator) {
    window.addEventListener("load", async () => {
      try {
        const registration = await navigator.serviceWorker.register("./service-worker.js");
        const offerUpdate = (worker) => {
          waitingWorker = worker;
          if (updateButton) updateButton.hidden = false;
        };

        if (registration.waiting) offerUpdate(registration.waiting);
        registration.addEventListener("updatefound", () => {
          const installingWorker = registration.installing;
          if (!installingWorker) return;
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

        configurePushButton(registration);
        await registration.update();
        document.addEventListener("visibilitychange", () => {
          if (document.visibilityState === "visible" && navigator.onLine) {
            registration.update().catch((error) => {
              console.error("No se pudo comprobar si hay una actualización de CaféLiz.", error);
            });
          }
        });
      } catch (error) {
        console.error("No se pudo registrar el service worker de CaféLiz.", error);
      }
    });
  }

  updateButton?.addEventListener("click", () => {
    if (waitingWorker) waitingWorker.postMessage({ type: "SKIP_WAITING" });
  });
})();

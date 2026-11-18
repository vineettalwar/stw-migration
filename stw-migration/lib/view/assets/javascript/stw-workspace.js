/* Accessible keyboard behavior for the retained migration progress engine. */
(() => {
  let activeDialog = null;
  let returnFocus = null;
  const focusable = (dialog) => Array.from(dialog.querySelectorAll('button,a[href],input:not([type="hidden"]),[tabindex="0"]')).filter((el) => !el.disabled && el.getClientRects().length);
  const update = () => {
    const dialog = Array.from(document.querySelectorAll('.ai1wm-modal-container')).find((el) => el.getClientRects().length);
    if (dialog && dialog !== activeDialog) {
      returnFocus = document.activeElement;
      activeDialog = dialog;
      dialog.setAttribute('role', 'dialog');
      dialog.setAttribute('aria-modal', 'true');
      dialog.setAttribute('aria-label', 'Migration progress');
      dialog.tabIndex = -1;
      (focusable(dialog)[0] || dialog).focus();
    } else if (!dialog && activeDialog) {
      activeDialog = null;
      if (returnFocus && returnFocus.isConnected) returnFocus.focus();
    }
  };
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || !activeDialog) return;
    const items = focusable(activeDialog);
    if (!items.length) {
      event.preventDefault();
      activeDialog.focus();
      return;
    }
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && (document.activeElement === first || !activeDialog.contains(document.activeElement))) {
      event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !activeDialog.contains(document.activeElement))) {
      event.preventDefault(); first.focus();
    }
  });
  new MutationObserver(update).observe(document.body, { childList: true, subtree: true });
  update();
})();

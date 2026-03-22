export function usePrint() {

  function printUrl(url) {
    // Create a hidden iframe
    const iframe = document.createElement('iframe')
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:0;height:0;border:0;'
    document.body.appendChild(iframe)

    iframe.onload = () => {
      try {
        // Focus the iframe window and print
        iframe.contentWindow.focus()
        iframe.contentWindow.print()
      } catch (e) {
        // Fallback: open in new tab if iframe blocked
        window.open(url, '_blank')
      }
      // Clean up after print dialog closes
      setTimeout(() => {
        document.body.removeChild(iframe)
      }, 1000)
    }

    iframe.src = url
  }

  function printChecklist(id) {
    if (id) printUrl(`/print/checklist/${id}`)
  }

  function printInvoice(id) {
    if (id) printUrl(`/print/invoice/${id}`)
  }

  return { printChecklist, printInvoice, printUrl }
}
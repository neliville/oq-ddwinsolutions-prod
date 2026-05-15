/**
 * Branding exports : fusion OUTILS-QUALITÉ (système) + préférences utilisateur.
 */
;(function () {
  const SYSTEM_DEFAULTS = {
    brandName: "OUTILS-QUALITÉ",
    website: "www.outils-qualite.com",
    copyright: "© OUTILS-QUALITÉ - www.outils-qualite.com",
    watermark: "OUTILS-QUALITÉ",
  }

  const LINE_HEIGHT = 18
  const USER_HEADER_FONT = "14px Arial, sans-serif"
  const USER_HEADER_COLOR = "#334155"
  const FOOTER_USER_FONT = "11px Arial, sans-serif"
  const FOOTER_USER_COLOR = "#64748b"
  const FOOTER_COPYRIGHT_FONT = "12px Arial, sans-serif"
  const FOOTER_COPYRIGHT_COLOR = "#94a3b8"

  let cache = null
  let loadPromise = null

  function normalizeBranding(raw) {
    const system = { ...SYSTEM_DEFAULTS, ...(raw?.system || {}) }
    const userRaw = raw?.user || {}
    const user = {}
    if (raw?.exportDisplayName || userRaw.displayName) {
      user.displayName = (raw.exportDisplayName || userRaw.displayName || "").trim() || null
    }
    if (raw?.exportJobTitle || userRaw.jobTitle) {
      user.jobTitle = (raw.exportJobTitle || userRaw.jobTitle || "").trim() || null
    }
    if (raw?.exportCompanyName || userRaw.companyName) {
      user.companyName = (raw.exportCompanyName || userRaw.companyName || "").trim() || null
    }
    const pdfFooter = (raw?.exportPdfFooter || userRaw.pdfFooter || "").trim()
    if (pdfFooter) {
      user.pdfFooter = pdfFooter
    }

    return { system, user, raw }
  }

  function getSystemDefaults() {
    return { ...SYSTEM_DEFAULTS }
  }

  function getUserLines(branding) {
    const u = branding?.user || {}
    return [u.displayName, u.jobTitle, u.companyName].filter((s) => s && String(s).trim() !== "")
  }

  function measureHeaderExtraHeight(lineCount) {
    return Math.max(0, lineCount) * LINE_HEIGHT
  }

  function computeHeaderHeight(branding, baseHeight) {
    const base = baseHeight ?? 108
    return base + measureHeaderExtraHeight(getUserLines(branding).length)
  }

  /**
   * En-tête standard : titre outil, lignes utilisateur, date, description.
   * @returns {number} Y sous la description
   */
  function paintStandardExportHeader(ctx, canvasWidth, headerHeight, options) {
    const {
      titleText,
      exportLocale,
      descriptionText = "",
      branding,
      titleFont = "bold 30px Arial, sans-serif",
      titleY = 44,
    } = options

    ctx.textAlign = "center"
    ctx.fillStyle = "#1f2937"
    ctx.font = titleFont
    ctx.fillText(titleText, canvasWidth / 2, titleY)

    let cursor = titleY + 18
    cursor = paintCanvasHeaderExtras(ctx, canvasWidth, cursor, branding)

    ctx.font = "16px Arial, sans-serif"
    ctx.fillStyle = "#475569"
    const dateY = Math.max(headerHeight - 24, cursor + 8)
    ctx.fillText(`Exporté le ${exportLocale}`, canvasWidth / 2, dateY)

    if (descriptionText) {
      ctx.font = "14px Arial, sans-serif"
      ctx.fillStyle = "#334155"
      ctx.fillText(String(descriptionText).substring(0, 150), canvasWidth / 2, headerHeight - 2)
    }

    return headerHeight
  }

  function paintCanvasHeaderExtras(ctx, canvasWidth, yStart, branding) {
    const lines = getUserLines(branding)
    if (!lines.length) {
      return yStart
    }
    ctx.save()
    ctx.textAlign = "center"
    ctx.font = USER_HEADER_FONT
    ctx.fillStyle = USER_HEADER_COLOR
    let y = yStart
    for (const line of lines) {
      ctx.fillText(line, canvasWidth / 2, y)
      y += LINE_HEIGHT
    }
    ctx.restore()
    return y
  }

  /**
   * Canvas composite standard (en-tête + capture + résumé + pied).
   */
  function buildBrandedExportCanvas(capturedCanvas, options) {
    const {
      branding,
      titleText,
      exportLocale,
      descriptionText = "",
      titleFont = "bold 30px Arial, sans-serif",
      padding = 56,
      footerHeight = 88,
      headerBaseHeight = 108,
      summaryPainter = null,
    } = options

    const headerHeight = computeHeaderHeight(branding, headerBaseHeight)
    const finalCanvas = document.createElement("canvas")
    finalCanvas.width = capturedCanvas.width + padding * 2
    finalCanvas.height = capturedCanvas.height + padding * 2 + headerHeight + footerHeight
    const ctx = finalCanvas.getContext("2d")

    ctx.fillStyle = "#ffffff"
    ctx.fillRect(0, 0, finalCanvas.width, finalCanvas.height)

    paintStandardExportHeader(ctx, finalCanvas.width, headerHeight, {
      titleText,
      exportLocale,
      descriptionText,
      branding,
      titleFont,
    })

    const contentOffsetY = headerHeight + padding
    ctx.drawImage(capturedCanvas, padding, contentOffsetY)

    paintWatermark(ctx, finalCanvas.width / 2, contentOffsetY + capturedCanvas.height / 2, branding)

    if (typeof summaryPainter === "function") {
      const summaryStart = contentOffsetY + capturedCanvas.height + padding
      summaryPainter(ctx, summaryStart, finalCanvas.width)
    }

    paintCanvasFooter(ctx, finalCanvas.width, finalCanvas.height, footerHeight, branding)

    return finalCanvas
  }

  function paintCanvasFooter(ctx, canvasWidth, canvasHeight, footerHeight, branding) {
    const customFooter = branding?.user?.pdfFooter
    const centerX = canvasWidth / 2
    const copyrightY = canvasHeight - footerHeight / 2

    ctx.save()
    ctx.textAlign = "center"
    if (customFooter) {
      ctx.font = FOOTER_USER_FONT
      ctx.fillStyle = FOOTER_USER_COLOR
      ctx.fillText(customFooter, centerX, copyrightY - 14)
    }
    ctx.font = FOOTER_COPYRIGHT_FONT
    ctx.fillStyle = FOOTER_COPYRIGHT_COLOR
    ctx.fillText(branding?.system?.copyright || SYSTEM_DEFAULTS.copyright, centerX, copyrightY)
    ctx.restore()
  }

  function paintWatermark(ctx, x, y, branding) {
    ctx.save()
    ctx.translate(x, y)
    ctx.rotate(-Math.PI / 6)
    ctx.font = "26px Arial, sans-serif"
    ctx.fillStyle = "rgba(148, 163, 184, 0.18)"
    ctx.textAlign = "center"
    ctx.fillText(branding?.system?.watermark || SYSTEM_DEFAULTS.watermark, 0, 0)
    ctx.restore()
  }

  function applyJsPdfHeader(pdf, branding, pageWidth, options) {
    const {
      titleText,
      exportLocale,
      titleY = 12,
      dateY = 18,
    } = options
    pdf.setFont("helvetica", "bold")
    pdf.setFontSize(16)
    pdf.setTextColor(31, 41, 55)
    pdf.text(titleText, pageWidth / 2, titleY, { align: "center" })

    let cursorY = titleY + 8
    const lines = getUserLines(branding)
    if (lines.length) {
      pdf.setFont("helvetica", "normal")
      pdf.setFontSize(10)
      pdf.setTextColor(51, 65, 85)
      for (const line of lines) {
        cursorY += 5
        pdf.text(line, pageWidth / 2, cursorY, { align: "center" })
      }
    }

    pdf.setFont("helvetica", "normal")
    pdf.setFontSize(10)
    pdf.setTextColor(71, 85, 105)
    const finalDateY = Math.max(dateY, cursorY + 6)
    pdf.text(`Exporté le ${exportLocale}`, pageWidth / 2, finalDateY, { align: "center" })

    return finalDateY
  }

  function applyJsPdfFooter(pdf, branding, pageHeight, pageWidth) {
    const customFooter = branding?.user?.pdfFooter
    const copyright = branding?.system?.copyright || SYSTEM_DEFAULTS.copyright
    const x = pageWidth ? pageWidth / 2 : 10
    const textOpts = pageWidth ? { align: "center" } : undefined
    pdf.setFontSize(8)
    pdf.setTextColor(150, 150, 150)
    if (customFooter) {
      pdf.setFontSize(7)
      pdf.text(customFooter, x, pageHeight - 10, textOpts)
      pdf.setFontSize(8)
    }
    pdf.text(copyright, x, pageHeight - (pageWidth ? 6 : 5), textOpts)
  }

  function enrichMetadata(metadata, branding) {
    const normalized = branding?.system ? branding : normalizeBranding(branding || {})
    const userLines = getUserLines(normalized)
    const userBlock = {}
    if (normalized.user?.displayName) userBlock.displayName = normalized.user.displayName
    if (normalized.user?.jobTitle) userBlock.jobTitle = normalized.user.jobTitle
    if (normalized.user?.companyName) userBlock.companyName = normalized.user.companyName
    if (normalized.user?.pdfFooter) userBlock.pdfFooter = normalized.user.pdfFooter

    const result = {
      ...metadata,
      source: metadata?.source || normalized.system.brandName,
      branding: {
        system: { ...normalized.system },
      },
    }
    if (Object.keys(userBlock).length > 0) {
      result.branding.user = userBlock
    }
    if (userLines.length > 0) {
      result.branding.headerLines = userLines
    }
    return result
  }

  async function load() {
    if (cache !== null) {
      return cache
    }
    if (loadPromise === null) {
      loadPromise = fetch("/api/user/export-branding", {
        method: "GET",
        headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      })
        .then(async (response) => {
          const ct = response.headers.get("content-type") || ""
          if (!response.ok || !ct.includes("application/json")) {
            return normalizeBranding({})
          }
          try {
            return normalizeBranding(await response.json())
          } catch {
            return normalizeBranding({})
          }
        })
        .catch(() => normalizeBranding({}))
    }
    cache = await loadPromise
    return cache
  }

  function invalidateCache() {
    cache = null
    loadPromise = null
  }

  window.OqExportBranding = {
    load,
    invalidateCache,
    getSystemDefaults,
    getUserLines,
    measureHeaderExtraHeight,
    computeHeaderHeight,
    paintStandardExportHeader,
    buildBrandedExportCanvas,
    paintCanvasHeaderExtras,
    paintCanvasFooter,
    paintWatermark,
    applyJsPdfHeader,
    applyJsPdfFooter,
    enrichMetadata,
    normalizeBranding,
    LINE_HEIGHT,
  }
})()

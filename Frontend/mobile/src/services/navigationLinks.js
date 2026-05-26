import { Linking, Platform } from 'react-native'

// Abre o Google Maps (ou Apple Maps em iOS sem GMaps instalado) com rota
// definida diretamente em modo de condução. Tenta primeiro o esquema nativo
// (abre a app instalada com a rota carregada) e cai para HTTPS universal
// (que o OS resolve para a app ou browser).
export async function openGoogleMaps({ lat, lng, label }) {
  if (lat === null || lng === null || lat === undefined || lng === undefined) {
    return false
  }

  const destination = `${lat},${lng}`
  const candidates = []

  if (Platform.OS === 'ios') {
    // Scheme da app Google Maps em iOS
    candidates.push(`comgooglemaps://?daddr=${destination}&directionsmode=driving`)
    // Apple Maps como fallback nativo
    candidates.push(`http://maps.apple.com/?daddr=${destination}&dirflg=d`)
  } else if (Platform.OS === 'android') {
    // Scheme Android nativo do Google Maps (abre directamente em navegacao)
    candidates.push(`google.navigation:q=${destination}&mode=d`)
  }

  // Fallback universal: HTTPS do Google Maps. O OS resolve para a app
  // instalada (se houver) ou abre no browser.
  candidates.push(
    `https://www.google.com/maps/dir/?api=1&destination=${destination}&travelmode=driving`,
  )

  return tryOpenAny(candidates, label)
}

// Abre o Waze com navegacao para as coordenadas dadas.
export async function openWaze({ lat, lng, label }) {
  if (lat === null || lng === null || lat === undefined || lng === undefined) {
    return false
  }

  const candidates = [
    `waze://?ll=${lat},${lng}&navigate=yes`,
    `https://waze.com/ul?ll=${lat},${lng}&navigate=yes`,
  ]

  return tryOpenAny(candidates, label)
}

async function tryOpenAny(urls, _label) {
  for (const url of urls) {
    try {
      // Em iOS, canOpenURL para esquemas não HTTP requer LSApplicationQueriesSchemes.
      // Em Android e mais permissivo. Em qualquer caso, openURL falha silenciosamente
      // Se o esquema não for resolúvel, apanhamos no catch e tentamos o próximo.
      const supported = await Linking.canOpenURL(url).catch(() => false)
      if (!supported && !url.startsWith('http')) {
        continue
      }
      await Linking.openURL(url)
      return true
    } catch {
      // Tenta a próxima.
    }
  }
  return false
}

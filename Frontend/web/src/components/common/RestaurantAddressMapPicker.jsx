import { useEffect, useMemo, useState } from 'react'
import { MapContainer, Marker, Popup, TileLayer, useMap, useMapEvents } from 'react-leaflet'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

const DEFAULT_CENTER = {
  lat: 41.1579,
  lng: -8.6291,
}

const restaurantIcon = new L.DivIcon({
  className: 'rb-address-map-pin',
  html:
    '<div class="rb-address-map-pin-shape">' +
    '<span>R</span>' +
    '</div>',
  iconSize: [34, 42],
  iconAnchor: [17, 42],
  popupAnchor: [0, -38],
})

function toPoint(latitude, longitude) {
  const lat = Number(latitude)
  const lng = Number(longitude)
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null
  return { lat, lng }
}

function MapClickHandler({ onPick }) {
  useMapEvents({
    click(event) {
      onPick({ latitude: event.latlng.lat, longitude: event.latlng.lng })
    },
  })

  return null
}

function SyncMapView({ marker }) {
  const map = useMap()

  useEffect(() => {
    map.invalidateSize()
  }, [map])

  useEffect(() => {
    if (!marker) return
    map.setView([marker.lat, marker.lng], Math.max(map.getZoom(), 15), {
      animate: true,
    })
  }, [map, marker])

  return null
}

export function RestaurantAddressMapPicker({
  latitude,
  longitude,
  onChange,
  height = 240,
}) {
  const selectedPoint = useMemo(() => toPoint(latitude, longitude), [latitude, longitude])
  const center = selectedPoint ?? DEFAULT_CENTER
  const [locationStatus, setLocationStatus] = useState('')

  function handlePick(point) {
    setLocationStatus('')
    onChange?.({
      latitude: point.latitude,
      longitude: point.longitude,
    })
  }

  function handleUseCurrentLocation() {
    if (!navigator.geolocation) {
      setLocationStatus('Localizacao indisponivel neste navegador.')
      return
    }

    setLocationStatus('A obter localizacao...')
    navigator.geolocation.getCurrentPosition(
      (position) => {
        setLocationStatus('')
        handlePick({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude,
        })
      },
      () => {
        setLocationStatus('Permissao de localizacao negada ou indisponivel.')
      },
      {
        enableHighAccuracy: false,
        timeout: 10000,
        maximumAge: 60000,
      },
    )
  }

  return (
    <div className="rb-address-map-picker">
      <div className="rb-address-map-head">
        <strong>Localizacao no mapa</strong>
        <button type="button" className="rb-icon-mini" onClick={handleUseCurrentLocation}>
          Usar localizacao
        </button>
      </div>

      <div className="rb-address-map-box" style={{ height }}>
        <MapContainer
          center={[center.lat, center.lng]}
          zoom={selectedPoint ? 15 : 13}
          style={{ width: '100%', height: '100%' }}
          scrollWheelZoom={false}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
            url="https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png"
            subdomains={['a', 'b', 'c', 'd']}
          />
          <MapClickHandler onPick={handlePick} />
          <SyncMapView marker={selectedPoint} />
          {selectedPoint ? (
            <Marker position={[selectedPoint.lat, selectedPoint.lng]} icon={restaurantIcon}>
              <Popup>Restaurante</Popup>
            </Marker>
          ) : null}
        </MapContainer>
      </div>

      <p className="rb-address-map-hint">
        Clica no mapa para definir a morada do restaurante. As coordenadas sao preenchidas automaticamente.
      </p>
      {locationStatus ? <p className="rb-address-map-status">{locationStatus}</p> : null}
    </div>
  )
}

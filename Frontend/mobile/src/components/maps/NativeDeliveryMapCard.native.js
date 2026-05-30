import { StyleSheet, View } from 'react-native'
import MapView, { Marker, Polyline } from 'react-native-maps'

function resolveRegion(points) {
  const valid = points.filter((point) => (
    point &&
    Number.isFinite(point.latitude) &&
    Number.isFinite(point.longitude)
  ))

  if (valid.length === 0) {
    return {
      latitude: 41.1579,
      longitude: -8.6291,
      latitudeDelta: 0.03,
      longitudeDelta: 0.03,
    }
  }

  const lats = valid.map((point) => Number(point.latitude))
  const lngs = valid.map((point) => Number(point.longitude))

  const minLat = Math.min(...lats)
  const maxLat = Math.max(...lats)
  const minLng = Math.min(...lngs)
  const maxLng = Math.max(...lngs)

  const latitude = (minLat + maxLat) / 2
  const longitude = (minLng + maxLng) / 2
  const latitudeDelta = Math.max(0.01, (maxLat - minLat) * 1.8)
  const longitudeDelta = Math.max(0.01, (maxLng - minLng) * 1.8)

  return {
    latitude,
    longitude,
    latitudeDelta,
    longitudeDelta,
  }
}

export function NativeDeliveryMapCard({ pickup, dropoff, courier, routePoints = [], positions = [] }) {
  function toCoord(point) {
    if (!point || point.lat === null || point.lat === undefined || point.lng === null || point.lng === undefined) {
      return null
    }

    const latitude = Number(point.lat)
    const longitude = Number(point.lng)

    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
      return null
    }

    return { latitude, longitude }
  }

  const pickupCoord =
    pickup ? toCoord(pickup) : null

  const dropoffCoord =
    dropoff ? toCoord(dropoff) : null

  const courierCoord =
    courier ? toCoord(courier) : null

  const region = resolveRegion([pickupCoord, dropoffCoord, courierCoord])

  const routeCoords = routePoints
    .map(toCoord)
    .filter(Boolean)

  const trackedPath = positions
    .map(toCoord)
    .filter(Boolean)

  const routeLine = [pickupCoord, dropoffCoord].filter(Boolean)
  const courierLine = trackedPath.length < 2 ? [courierCoord, dropoffCoord].filter(Boolean) : []

  return (
    <View style={styles.wrapper}>
      <MapView style={styles.map} initialRegion={region} region={region}>
        {pickupCoord ? <Marker coordinate={pickupCoord} title={pickup?.label ?? 'Pickup'} /> : null}
        {dropoffCoord ? <Marker coordinate={dropoffCoord} title={dropoff?.label ?? 'Dropoff'} /> : null}
        {courierCoord ? <Marker coordinate={courierCoord} title={courier?.label ?? 'Estafeta'} /> : null}
        {routeCoords.length >= 2 ? (
          <Polyline coordinates={routeCoords} strokeColor="#f97316" strokeWidth={3} />
        ) : routeLine.length === 2 ? (
          <Polyline coordinates={routeLine} strokeColor="#f97316" strokeWidth={3} />
        ) : null}
        {trackedPath.length >= 2 ? (
          <Polyline coordinates={trackedPath} strokeColor="#2563eb" strokeWidth={3} />
        ) : null}
        {courierLine.length === 2 ? (
          <Polyline coordinates={courierLine} strokeColor="#2563eb" strokeWidth={3} lineDashPattern={[8, 8]} />
        ) : null}
      </MapView>
    </View>
  )
}

const styles = StyleSheet.create({
  wrapper: {
    height: 240,
    borderRadius: 14,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#dfe4ec',
    backgroundColor: '#fff',
    marginBottom: 12,
  },
  map: {
    width: '100%',
    height: '100%',
  },
})

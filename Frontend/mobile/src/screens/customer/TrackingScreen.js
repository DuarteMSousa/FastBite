import { Pressable, ScrollView, Text, View } from 'react-native'
import { NativeDeliveryMapCard } from '../../components/maps/NativeDeliveryMapCard'
import { styles } from './styles'
import { ICON, eventTypeLabel, formatCurrency, orderItemStatusChipStyle, orderItemStatusLabel, statusLabel } from './utils'

export function TrackingScreen({
  tracking,
  checkout,
  isOnline,
  onBack,
  onRefresh,
  onOpenChatRestaurant,
  onOpenChatCourier,
  chatLoading,
}) {
  const events = tracking?.events ?? []
  const hasCourierPosition = Boolean(tracking?.latest_position)
  const courierAssigned = Boolean(tracking?.courier_id)

  return (
    <View style={styles.screen}>
      <View style={styles.trackHeader}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={styles.backArrow}>{ICON.back}</Text>
        </Pressable>

        <Text style={styles.trackTitle}>Acompanhar pedido</Text>
        <Text style={styles.trackSub}>{tracking?.restaurant_name ?? 'Restaurante'}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {!isOnline ? (
          <View style={styles.offlineBanner}>
            <Text style={styles.offlineBannerText}>
              Sem internet. O acompanhamento será atualizado automaticamente quando a ligação voltar.
            </Text>
          </View>
        ) : null}

        {checkout?.payment_status === 'COMPLETED' ? (
          <View style={styles.paymentSuccessBanner}>
            <Text style={styles.paymentSuccessTitle}>Pagamento confirmado</Text>
            <Text style={styles.paymentSuccessText}>
              Total: EUR {Number(checkout.total ?? 0).toFixed(2)} via{' '}
              {checkout.payment_method ?? 'desconhecido'}
            </Text>
          </View>
        ) : null}

        <Pressable style={styles.successBanner} onPress={onRefresh}>
          <Text style={styles.successBannerText}>{ICON.check} Atualizar acompanhamento</Text>
        </Pressable>

        <View style={styles.chatButtonsRow}>
          <Pressable
            style={styles.chatButton}
            onPress={onOpenChatRestaurant}
            disabled={chatLoading}
          >
            <Text style={styles.chatButtonText}>
              {chatLoading ? 'A abrir...' : 'Chat com restaurante'}
            </Text>
          </Pressable>
          {tracking?.courier_id ? (
            <Pressable
              style={styles.chatButton}
              onPress={onOpenChatCourier}
              disabled={chatLoading}
            >
              <Text style={styles.chatButtonText}>Chat com estafeta</Text>
            </Pressable>
          ) : null}
        </View>

        <View style={styles.trackCard}>
          <Text style={styles.trackSummaryTitle}>Estado atual</Text>
          <Text style={styles.trackSummarySub}>{statusLabel(tracking?.order_status)}</Text>
          <Text style={styles.trackSummarySub}>Entrega: {statusLabel(tracking?.delivery_status)}</Text>
          <Text style={styles.trackSummarySub}>
            Posição do estafeta: {hasCourierPosition ? 'disponível no mapa' : courierAssigned ? 'a aguardar a primeira posição...' : 'sem estafeta atribuído'}
          </Text>
          <Text style={styles.trackSummarySub}>
            Distância restante: {tracking?.distance_km_remaining ?? '-'} km
          </Text>
          <Text style={styles.trackSummarySub}>
            ETA: {tracking?.eta_seconds ? `${Math.ceil(tracking.eta_seconds / 60)} min` : '-'}
          </Text>
          <Text style={styles.trackSummarySub}>Total: {formatCurrency(checkout?.total ?? 0)}</Text>
        </View>

        <NativeDeliveryMapCard
          title="Mapa da entrega"
          subtitle={
            hasCourierPosition
              ? 'Posição atual do estafeta'
              : courierAssigned
                ? 'A aguardar a posição do estafeta'
                : 'Estafeta ainda não atribuído'
          }
          pickup={
            tracking?.pickup_latitude !== null && tracking?.pickup_latitude !== undefined
              ? {
                  lat: tracking.pickup_latitude,
                  lng: tracking.pickup_longitude,
                  label: 'Recolha',
                }
              : null
          }
          dropoff={
            tracking?.dropoff_latitude !== null && tracking?.dropoff_latitude !== undefined
              ? {
                  lat: tracking.dropoff_latitude,
                  lng: tracking.dropoff_longitude,
                  label: 'Entrega',
                }
              : null
          }
          courier={
            tracking?.latest_position
              ? {
                  lat: tracking.latest_position.lat,
                  lng: tracking.latest_position.lng,
                  label: 'Estafeta',
                }
              : null
          }
          routePoints={tracking?.route_points ?? []}
          positions={tracking?.positions ?? []}
        />

        {(tracking?.items ?? []).length > 0 ? (
          <View style={styles.trackDetailsCard}>
            <Text style={styles.sectionTitle}>Pratos do pedido</Text>
            {tracking.items.map((item) => (
              <View key={item.id} style={styles.summaryLine}>
                <Text style={styles.summaryLabel}>
                  {item.quantity}x {item.product_name}
                </Text>
                <Text style={[styles.orderStatusChip, orderItemStatusChipStyle(item.status)]}>
                  {orderItemStatusLabel(item.status)}
                </Text>
              </View>
            ))}
          </View>
        ) : null}

        <View style={styles.trackDetailsCard}>
          <Text style={styles.sectionTitle}>Eventos</Text>
          {events.length === 0 ? <Text style={styles.mutedText}>Sem eventos ainda.</Text> : null}
          {events.map((event) => (
            <View key={`${event.event_type}-${event.timestamp}`} style={styles.summaryLine}>
              <Text style={styles.summaryLabel}>{eventTypeLabel(event.event_type)}</Text>
              <Text style={styles.summaryValue}>
                {event.timestamp ? new Date(event.timestamp).toLocaleTimeString() : '-'}
              </Text>
            </View>
          ))}
        </View>

      </ScrollView>
    </View>
  )
}


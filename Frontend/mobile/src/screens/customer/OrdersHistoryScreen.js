import { Pressable, RefreshControl, ScrollView, Text, View } from 'react-native'
import { styles } from './styles'
import { CANCELLABLE_STATUSES, ICON, TRACKABLE_STATUSES, orderStatusChipStyle, statusLabel } from './utils'

export function OrdersHistoryScreen({
  orders,
  loading,
  busyOrderId,
  onBack,
  onRefresh,
  onCancel,
  onRepeat,
  onTrack,
  onReview,
  hasReviewFor,
  onOpenDetail,
  onLoadMore,
  hasMore,
}) {
  return (
    <View style={styles.screen}>
      <View style={styles.trackHeader}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={styles.backArrow}>{ICON.back}</Text>
        </Pressable>
        <Text style={styles.trackTitle}>Meus pedidos</Text>
        <Text style={styles.trackSub}>Histórico de encomendas</Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl refreshing={Boolean(loading)} onRefresh={onRefresh} tintColor="#3479ed" />
        }
      >
        <Pressable style={styles.successBanner} onPress={onRefresh} disabled={loading}>
          <Text style={styles.successBannerText}>
            {loading ? 'A carregar...' : 'Atualizar pedidos'}
          </Text>
        </Pressable>

        {!loading && orders.length === 0 ? (
          <View style={styles.emptyStateCard}>
            <Text style={styles.emptyStateTitle}>Sem pedidos</Text>
            <Text style={styles.emptyStateText}>O histórico fica aqui depois da primeira encomenda.</Text>
          </View>
        ) : null}

        {orders.length > 0 && hasMore ? null : orders.length > 0 ? (
          <Text style={styles.mutedText}>Fim do histórico.</Text>
        ) : null}

        {orders.map((order) => {
          const canCancel = CANCELLABLE_STATUSES.includes(order.status)
          const canTrack = TRACKABLE_STATUSES.includes(order.status)
          const canReview = order.status === 'DELIVERED'
          const isBusy = busyOrderId === order.id
          const hasRestaurantReview = canReview && order.restaurant_id
            ? hasReviewFor?.('RESTAURANT', order.restaurant_id)
            : false
          const hasCourierReview = canReview && order.courier_id
            ? hasReviewFor?.('COURIER', order.courier_id)
            : false

          return (
            <View key={order.id} style={styles.orderCard}>
              <View style={styles.orderCardTop}>
                <View style={styles.orderCardHeading}>
                  <Text style={styles.orderCardRestaurant}>{order.restaurant_name ?? '-'}</Text>
                  <Text style={styles.orderCardDate}>
                    {order.created_at ? new Date(order.created_at).toLocaleString() : '-'}
                  </Text>
                </View>
                <Text style={[styles.orderStatusChip, orderStatusChipStyle(order.status)]}>
                  {statusLabel(order.status)}
                </Text>
              </View>

              {order.items_summary ? (
                <Text style={styles.orderCardItems} numberOfLines={2}>
                  {order.items_summary}
                </Text>
              ) : null}

              <View style={styles.orderCardFooter}>
                <Text style={styles.orderCardTotal}>{`EUR ${Number(order.total ?? 0).toFixed(2)}`}</Text>
                <View style={styles.orderCardActions}>
                  {canTrack ? (
                    <Pressable
                      style={styles.orderActionBtn}
                      onPress={() => onTrack(order)}
                      disabled={isBusy}
                    >
                      <Text style={styles.orderActionBtnText}>Acompanhar</Text>
                    </Pressable>
                  ) : null}
                  <Pressable
                    style={styles.orderActionBtn}
                    onPress={() => onOpenDetail(order)}
                  >
                    <Text style={styles.orderActionBtnText}>Detalhe</Text>
                  </Pressable>
                  <Pressable
                    style={styles.orderActionBtn}
                    onPress={() => onRepeat(order)}
                    disabled={isBusy}
                  >
                    <Text style={styles.orderActionBtnText}>
                      {isBusy ? 'A repetir...' : 'Repetir'}
                    </Text>
                  </Pressable>
                  {canCancel ? (
                    <Pressable
                      style={[styles.orderActionBtn, styles.orderActionDanger]}
                      onPress={() => onCancel(order)}
                      disabled={isBusy}
                    >
                      <Text style={[styles.orderActionBtnText, styles.orderActionDangerText]}>
                        Cancelar
                      </Text>
                    </Pressable>
                  ) : null}
                  {canReview && order.restaurant_id ? (
                    <Pressable
                      style={styles.orderActionBtn}
                      onPress={() => onReview(order, 'RESTAURANT', order.restaurant_id)}
                    >
                      <Text style={styles.orderActionBtnText}>
                        {hasRestaurantReview ? 'Editar restaurante' : 'Avaliar restaurante'}
                      </Text>
                    </Pressable>
                  ) : null}
                  {canReview && order.courier_id ? (
                    <Pressable
                      style={styles.orderActionBtn}
                      onPress={() => onReview(order, 'COURIER', order.courier_id)}
                    >
                      <Text style={styles.orderActionBtnText}>
                        {hasCourierReview ? 'Editar estafeta' : 'Avaliar estafeta'}
                      </Text>
                    </Pressable>
                  ) : null}
                </View>
              </View>
            </View>
          )
        })}

        {orders.length > 0 && hasMore ? (
          <Pressable
            style={[styles.addressAddBtn, { marginTop: 12 }]}
            onPress={onLoadMore}
            disabled={loading}
          >
            <Text style={styles.addressAddBtnText}>
              {loading ? 'A carregar...' : 'Carregar mais'}
            </Text>
          </Pressable>
        ) : null}
      </ScrollView>
    </View>
  )
}


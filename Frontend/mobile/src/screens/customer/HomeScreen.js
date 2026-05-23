import { Pressable, RefreshControl, ScrollView, Text, TextInput, View } from 'react-native'
import { styles } from './styles'
import { ICON } from './utils'

export function HomeScreen({
  restaurants,
  loading,
  isOnline,
  pushStatus,
  notificationState,
  notificationPreview,
  availableCouriers,
  onOpenRestaurant,
  onOpenTracking,
  hasActiveOrder,
  onOpenProfile,
  inboxUnreadCount,
  onOpenInbox,
  onOpenOrders,
  filters,
  onChangeFilters,
  onApplyFilters,
  onResetFilters,
}) {
  const noCouriersAvailable = availableCouriers === 0
  return (
    <View style={styles.screen}>
      <View style={styles.homeHeader}>
        <View style={styles.homeHeaderTop}>
          <View>
            <Text style={styles.brand}>FastBite</Text>
            <Text style={styles.subtitle}>O que deseja comer hoje?</Text>
          </View>
          <View style={styles.headerActions}>
            <Pressable style={styles.bellButton} onPress={onOpenInbox}>
              <Text style={styles.bellIcon}>{ICON.bell}</Text>
              {inboxUnreadCount > 0 ? (
                <View style={styles.bellBadge}>
                  <Text style={styles.bellBadgeText}>
                    {inboxUnreadCount > 99 ? '99+' : inboxUnreadCount}
                  </Text>
                </View>
              ) : null}
            </Pressable>
            <Pressable style={styles.profileButton} onPress={onOpenProfile}>
              <Text style={styles.profileIcon}>{ICON.user}</Text>
            </Pressable>
          </View>
        </View>

        <View style={styles.searchField}>
          <Text style={styles.searchIcon}>{ICON.search}</Text>
          <TextInput
            style={styles.searchInput}
            value={filters?.q ?? ''}
            placeholder="Procurar restaurantes..."
            placeholderTextColor="#dbe7ff"
            onChangeText={(text) =>
              onChangeFilters?.((current) => ({ ...current, q: text }))
            }
            onSubmitEditing={onApplyFilters}
            returnKeyType="search"
          />
        </View>

        <View style={styles.filterRow}>
          <TextInput
            style={styles.filterInput}
            value={filters?.city ?? ''}
            placeholder="Cidade"
            placeholderTextColor="#dbe7ff"
            onChangeText={(text) =>
              onChangeFilters?.((current) => ({ ...current, city: text }))
            }
          />
          <TextInput
            style={styles.filterInput}
            value={filters?.postalCode ?? ''}
            placeholder="Cod. postal"
            placeholderTextColor="#dbe7ff"
            onChangeText={(text) =>
              onChangeFilters?.((current) => ({ ...current, postalCode: text }))
            }
          />
          <Pressable style={styles.filterApply} onPress={onApplyFilters}>
            <Text style={styles.filterApplyText}>Filtrar</Text>
          </Pressable>
          <Pressable style={styles.filterReset} onPress={onResetFilters}>
            <Text style={styles.filterResetText}>Limpar</Text>
          </Pressable>
        </View>

        {!isOnline ? (
          <View style={styles.offlineBanner}>
            <Text style={styles.offlineBannerText}>Sem internet. A app entrou em modo offline.</Text>
          </View>
        ) : null}

        {noCouriersAvailable ? (
          <View style={styles.offlineBanner}>
            <Text style={styles.offlineBannerText}>
              Sem estafetas disponiveis neste momento. Nao e possivel fazer pedidos.
            </Text>
          </View>
        ) : null}

        {notificationPreview ? (
          <Pressable style={styles.notificationBanner} onPress={onOpenInbox}>
            <Text style={styles.notificationBannerTitle}>
              {ICON.bell} {notificationPreview.title}
            </Text>
            <Text style={styles.notificationBannerText}>
              {notificationPreview.message} Â· toca para abrir inbox
            </Text>
          </Pressable>
        ) : null}

        <View style={styles.headerActionsRow}>
          {hasActiveOrder ? (
            <Pressable style={styles.activeOrderBtn} onPress={onOpenTracking}>
              <View
                style={[
                  styles.statusDot,
                  notificationState === 'live'
                    ? styles.statusDotLive
                    : notificationState === 'connecting'
                      ? styles.statusDotConnecting
                      : styles.statusDotOffline,
                ]}
              />
              <Text style={styles.activeOrderBtnText}>Ver pedido ativo</Text>
            </Pressable>
          ) : null}

          <Pressable style={styles.ordersLink} onPress={onOpenOrders}>
            <Text style={styles.ordersLinkText}>Meus pedidos</Text>
          </Pressable>
        </View>

        {pushStatus === 'permission_denied' || pushStatus === 'error' ? (
          <Text style={styles.pushChip}>
            Push desativado Â· ativa nas definicoes para receber atualizacoes
          </Text>
        ) : null}
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={Boolean(loading)}
            onRefresh={onApplyFilters}
            tintColor="#3479ed"
          />
        }
      >
        <Text style={styles.sectionTitle}>Restaurantes</Text>
        {loading && restaurants.length === 0 ? <Text style={styles.mutedText}>A carregar...</Text> : null}
        {restaurants.map((item) => (
          <Pressable
            key={item.id}
            style={styles.restaurantCard}
            onPress={() => onOpenRestaurant(item.id)}
          >
            <View style={styles.restaurantStripe} />
            <View style={styles.restaurantBody}>
              <View style={styles.restaurantIconCircle}>
                <Text style={styles.restaurantIcon}>{'\u{1F355}'}</Text>
              </View>
              <View style={styles.restaurantInfo}>
                <Text style={styles.restaurantName} numberOfLines={1}>
                  {item.name}
                </Text>
                <Text style={styles.cuisine} numberOfLines={1}>
                  {item.city || 'Cidade nao definida'}
                </Text>
              </View>
              <View style={styles.ratingPill}>
                <Text style={styles.ratingText}>
                  {ICON.star} {Number(item.rating ?? 0).toFixed(1)}
                </Text>
              </View>
            </View>
          </Pressable>
        ))}
      </ScrollView>
    </View>
  )
}


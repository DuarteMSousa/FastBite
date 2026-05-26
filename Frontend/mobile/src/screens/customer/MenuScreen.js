import { Pressable, ScrollView, Text, View } from 'react-native'
import { styles } from './styles'
import { ICON, formatCurrency } from './utils'

export function MenuScreen({
  restaurant,
  items,
  itemCount,
  total,
  loading,
  availableCouriers,
  onBack,
  onAdd,
  onOpenCart,
  activeCategory,
  onChangeCategory,
}) {
  const noCouriersAvailable = availableCouriers === 0
  const categories = ['Todas', ...Array.from(new Set(items.map((item) => item.category || 'Sem categoria').filter(Boolean)))]
  const visibleItems =
    !activeCategory || activeCategory === 'Todas'
      ? items
      : items.filter((item) => (item.category || 'Sem categoria') === activeCategory)

  const groupedItems = visibleItems.reduce((acc, item) => {
    const cat = item.category || 'Sem categoria'
    if (!acc[cat]) acc[cat] = []
    acc[cat].push(item)
    return acc
  }, {})

  return (
    <View style={styles.screen}>
      <View style={styles.menuHeader}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={styles.backArrow}>{ICON.back}</Text>
        </Pressable>
        <Text style={styles.menuHeaderTitle}>{restaurant?.name ?? 'Restaurante'}</Text>
      </View>

      {noCouriersAvailable ? (
        <View style={styles.offlineBanner}>
          <Text style={styles.offlineBannerText}>
            Sem estafetas disponíveis. Não é possível finalizar o pedido neste momento.
          </Text>
        </View>
      ) : null}

      <ScrollView
        horizontal
        style={styles.categoryStrip}
        contentContainerStyle={styles.categoryStripContent}
        showsHorizontalScrollIndicator={false}
      >
        {categories.map((category) => (
          <Pressable
            key={category}
            style={[
              styles.categoryChip,
              (activeCategory || 'Todas') === category ? styles.categoryChipActive : null,
            ]}
            onPress={() => onChangeCategory(category === 'Todas' ? '' : category)}
          >
            <Text
              style={[
                styles.categoryChipText,
                (activeCategory || 'Todas') === category ? styles.categoryChipTextActive : null,
              ]}
            >
              {category}
            </Text>
          </Pressable>
        ))}
      </ScrollView>

      <ScrollView
        contentContainerStyle={[styles.scrollContent, itemCount > 0 ? styles.withCartBar : null]}
        showsVerticalScrollIndicator={false}
      >
        {loading && items.length === 0 ? (
          <View style={styles.emptyStateCard}>
            <Text style={styles.emptyStateTitle}>A carregar...</Text>
          </View>
        ) : null}
        {!loading && visibleItems.length === 0 ? (
          <View style={styles.emptyStateCard}>
            <Text style={styles.emptyStateTitle}>Sem pratos</Text>
            <Text style={styles.emptyStateText}>Não há produtos nesta categoria.</Text>
          </View>
        ) : null}

        {Object.entries(groupedItems).map(([category, group]) => (
          <View key={category}>
            <Text style={styles.sectionTitle}>{category}</Text>
            {group.map((item) => (
              <View key={item.restaurant_product_id} style={styles.menuCard}>
                <View style={styles.menuThumb}>
                  <Text style={styles.menuThumbEmoji}>{'\u{1F355}'}</Text>
                </View>
                <View style={styles.menuInfo}>
                  <Text style={styles.menuName}>{item.name ?? 'Produto'}</Text>
                  <Text style={styles.menuDescription}>{item.description ?? 'Sem descrição'}</Text>
                  <Text style={styles.menuPrice}>{formatCurrency(item.price)}</Text>
                  <Text style={styles.menuRate}>
                    {item.is_available ? 'Disponível' : 'Indisponível'}
                  </Text>
                </View>

                <Pressable
                  style={styles.addButton}
                  onPress={() => onAdd(item)}
                  disabled={!item.is_available}
                >
                  <Text style={styles.addButtonText}>{ICON.plus}</Text>
                </Pressable>
              </View>
            ))}
          </View>
        ))}
      </ScrollView>

      {itemCount > 0 ? (
        <Pressable style={styles.cartBar} onPress={onOpenCart}>
          <Text style={styles.cartBarText}>{ICON.cart} {itemCount} item</Text>
          <Text style={styles.cartBarText}>Ver carrinho</Text>
          <Text style={styles.cartBarText}>{formatCurrency(total)}</Text>
        </Pressable>
      ) : null}
    </View>
  )
}


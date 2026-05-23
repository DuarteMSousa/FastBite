import { Pressable, ScrollView, Text, TextInput, View } from 'react-native'
import { SummaryLine } from './SummaryLine'
import { styles } from './styles'
import { ICON, formatCurrency, paymentMethodLabel } from './utils'

export function CartScreen({
  items,
  subtotal,
  deliveryFee,
  discountTotal = 0,
  appliedDiscounts = [],
  couponValid = false,
  couponError = null,
  previewLoading = false,
  total,
  loading,
  availableCouriers,
  onDecrease,
  onIncrease,
  onRemove,
  onPlaceOrder,
  selectedAddress,
  paymentMethod,
  couponCode,
  onChangeCouponCode,
  onOpenAddressPicker,
  onOpenPaymentPicker,
}) {
  const noCouriersAvailable = availableCouriers === 0
  return (
    <View style={styles.screen}>
      <ScrollView
        contentContainerStyle={[styles.scrollContent, styles.cartScroll]}
        showsVerticalScrollIndicator={false}
      >
        {noCouriersAvailable ? (
          <View style={styles.offlineBanner}>
            <Text style={styles.offlineBannerText}>
              Sem estafetas disponiveis. Nao podes finalizar o pedido agora.
            </Text>
          </View>
        ) : null}

        {items.length === 0 ? <Text style={styles.mutedText}>Carrinho vazio.</Text> : null}

        {items.map((item) => (
          <View style={styles.cartCard} key={item.id}>
            <View style={styles.menuThumb}>
              <Text style={styles.menuThumbEmoji}>{'\u{1F355}'}</Text>
            </View>

            <View style={styles.cartInfo}>
              <Text style={styles.menuName}>{item.product_name}</Text>
              <Text style={styles.cartPrice}>{formatCurrency(item.unit_price)}</Text>
              <View style={styles.qtyControl}>
                <Pressable style={styles.qtyButton} onPress={() => onDecrease(item.id, item.quantity)}>
                  <Text style={styles.qtyText}>{ICON.minus}</Text>
                </Pressable>
                <Text style={styles.qtyValue}>{item.quantity}</Text>
                <Pressable style={styles.qtyButton} onPress={() => onIncrease(item.id, item.quantity)}>
                  <Text style={styles.qtyText}>{ICON.plus}</Text>
                </Pressable>
              </View>
            </View>

            <Pressable style={styles.removeButton} onPress={() => onRemove(item.id)}>
              <Text style={styles.removeText}>{ICON.close}</Text>
            </Pressable>
          </View>
        ))}

        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Detalhes da entrega</Text>

          <Pressable style={styles.checkoutRow} onPress={onOpenAddressPicker}>
            <View style={styles.checkoutRowText}>
              <Text style={styles.checkoutRowLabel}>Morada de entrega</Text>
              <Text style={styles.checkoutRowValue} numberOfLines={2}>
                {selectedAddress
                  ? `${selectedAddress.label ? selectedAddress.label + ' - ' : ''}${selectedAddress.street}, ${selectedAddress.city}`
                  : 'Escolher morada'}
              </Text>
            </View>
            <Text style={styles.checkoutRowArrow}>{'>'}</Text>
          </Pressable>

          <Pressable style={styles.checkoutRow} onPress={onOpenPaymentPicker}>
            <View style={styles.checkoutRowText}>
              <Text style={styles.checkoutRowLabel}>Metodo de pagamento</Text>
              <Text style={styles.checkoutRowValue}>{paymentMethodLabel(paymentMethod)}</Text>
            </View>
            <Text style={styles.checkoutRowArrow}>{'>'}</Text>
          </Pressable>

          <View style={styles.couponRow}>
            <Text style={styles.checkoutRowLabel}>Cupao</Text>
            <TextInput
              style={styles.couponInput}
              value={couponCode}
              onChangeText={onChangeCouponCode}
              placeholder="Codigo de cupao (opcional)"
              placeholderTextColor="#94a3b8"
              autoCapitalize="characters"
            />
          </View>
          {couponCode?.trim() ? (
            couponError ? (
              <Text style={styles.errorText}>Cupao invalido: {couponError}</Text>
            ) : couponValid ? (
              <Text style={styles.successText}>{ICON.check} Cupao aplicado.</Text>
            ) : null
          ) : null}
        </View>
      </ScrollView>

      <View style={styles.checkoutBar}>
        <SummaryLine label="Subtotal" value={formatCurrency(subtotal)} />
        <SummaryLine
          label={`Taxa de entrega${selectedAddress ? '' : ' (escolhe morada)'}`}
          value={formatCurrency(deliveryFee)}
        />
        {appliedDiscounts.length > 0 ? (
          appliedDiscounts.map((discount, index) => (
            <SummaryLine
              key={`${discount.name}-${index}`}
              label={discount.name}
              value={`- ${formatCurrency(discount.amount)}`}
            />
          ))
        ) : null}
        {discountTotal > 0 ? (
          <SummaryLine label="Desconto total" value={`- ${formatCurrency(discountTotal)}`} />
        ) : null}
        {previewLoading ? <Text style={styles.mutedText}>A calcular...</Text> : null}

        <View style={styles.totalRow}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalValue}>{formatCurrency(total)}</Text>
        </View>

        <Pressable
          style={styles.orderButton}
          onPress={onPlaceOrder}
          disabled={loading || items.length === 0 || noCouriersAvailable}
        >
          <Text style={styles.orderButtonText}>
            {loading
              ? 'A processar...'
              : noCouriersAvailable
                ? 'Sem estafetas disponiveis'
                : 'Fazer Pedido'}
          </Text>
        </Pressable>
      </View>
    </View>
  )
}


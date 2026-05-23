import { Pressable, ScrollView, Text, TextInput, View } from 'react-native'
import { styles } from './styles'
import { ICON } from './utils'

export function ProfileScreen({
  session,
  profileDraft,
  onChangeDraft,
  isSavingProfile,
  onSave,
  onBack,
  onLogoutRequest,
  addresses,
  onOpenAddresses,
  onOpenReviewsHistory,
  reviewsCount,
  onOpenOrdersHistory,
}) {
  return (
    <View style={styles.screen}>
      <View style={styles.trackHeader}>
        <Pressable onPress={onBack} style={styles.backButton}>
          <Text style={styles.backArrow}>{ICON.back}</Text>
        </Pressable>
        <Text style={styles.trackTitle}>Perfil</Text>
        <Text style={styles.trackSub}>{session?.email ?? '-'}</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Dados pessoais</Text>

          <Text style={styles.checkoutRowLabel}>Nome</Text>
          <TextInput
            style={styles.couponInput}
            value={profileDraft.name}
            onChangeText={(text) => onChangeDraft((current) => ({ ...current, name: text }))}
            placeholder="Nome completo"
            placeholderTextColor="#94a3b8"
          />

          <Text style={styles.checkoutRowLabel}>Email</Text>
          <TextInput
            style={styles.couponInput}
            value={profileDraft.email}
            onChangeText={(text) => onChangeDraft((current) => ({ ...current, email: text }))}
            placeholder="email@dominio.pt"
            placeholderTextColor="#94a3b8"
            autoCapitalize="none"
            keyboardType="email-address"
          />

          <Pressable
            style={[styles.orderButton, isSavingProfile ? { opacity: 0.6 } : null]}
            onPress={onSave}
            disabled={isSavingProfile}
          >
            <Text style={styles.orderButtonText}>
              {isSavingProfile ? 'A guardar...' : 'Guardar perfil'}
            </Text>
          </Pressable>
        </View>

        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Moradas</Text>
          <Text style={styles.checkoutRowValue}>
            {addresses.length === 0
              ? 'Sem moradas guardadas.'
              : `${addresses.length} morada(s) guardada(s).`}
          </Text>
          <Pressable style={[styles.addressAddBtn, { marginTop: 12 }]} onPress={onOpenAddresses}>
            <Text style={styles.addressAddBtnText}>Gerir moradas</Text>
          </Pressable>
        </View>

        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Meus pedidos</Text>
          <Text style={styles.checkoutRowValue}>
            Histórico completo de encomendas, com detalhe, repetir e cancelar.
          </Text>
          <Pressable
            style={[styles.addressAddBtn, { marginTop: 12 }]}
            onPress={onOpenOrdersHistory}
          >
            <Text style={styles.addressAddBtnText}>Ver histórico</Text>
          </Pressable>
        </View>

        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Minhas avaliações</Text>
          <Text style={styles.checkoutRowValue}>
            {reviewsCount === 0
              ? 'Sem avaliações ainda.'
              : `${reviewsCount} avaliação(ões) submetida(s).`}
          </Text>
          <Pressable
            style={[styles.addressAddBtn, { marginTop: 12 }]}
            onPress={onOpenReviewsHistory}
          >
            <Text style={styles.addressAddBtnText}>Ver avaliações</Text>
          </Pressable>
        </View>

        <View style={styles.checkoutCard}>
          <Text style={styles.checkoutSectionTitle}>Sessão</Text>
          <Pressable
            style={[styles.cancelDanger, { marginTop: 8 }]}
            onPress={onLogoutRequest}
          >
            <Text style={styles.cancelDangerText}>Terminar sessão</Text>
          </Pressable>
        </View>
      </ScrollView>
    </View>
  )
}

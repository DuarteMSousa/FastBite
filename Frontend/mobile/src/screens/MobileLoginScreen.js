import { useState } from 'react'
import { Pressable, SafeAreaView, StyleSheet, Text, TextInput, View } from 'react-native'
import { loginMobileUser, registerMobileUser } from '../services/authService'
import { useAutoToast } from '../components/common/ToastProvider'

export function MobileLoginScreen({ onLogin }) {
  const [activeMode, setActiveMode] = useState('login')
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [role, setRole] = useState('customer')
  const [loadingAction, setLoadingAction] = useState('')
  const [errorText, setErrorText] = useState('')

  useAutoToast({ message: errorText, kind: 'error' })

  async function handleLogin() {
    try {
      setLoadingAction('login')
      const session = await loginMobileUser({
        email,
        password,
      })
      setErrorText('')
      onLogin(session)
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoadingAction('')
    }
  }

  async function handleRegister() {
    try {
      setLoadingAction('register')
      const session = await registerMobileUser({
        name,
        email,
        password,
        role,
      })
      setErrorText('')
      onLogin(session)
    } catch (error) {
      setErrorText(error.message)
    } finally {
      setLoadingAction('')
    }
  }

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.container}>
        <Text style={styles.brand}>FastBite</Text>
        <Text style={styles.subtitle}>
          {activeMode === 'login' ? 'Inicia sessão' : 'Cria a tua conta'}
        </Text>
        <View style={styles.formCard}>
          <View style={styles.modeRow}>
            <ModeButton
              label="Entrar"
              active={activeMode === 'login'}
              onPress={() => {
                setActiveMode('login')
                setErrorText('')
              }}
            />
            <ModeButton
              label="Criar conta"
              active={activeMode === 'register'}
              onPress={() => {
                setActiveMode('register')
                setErrorText('')
              }}
            />
          </View>

          {activeMode === 'register' ? (
            <>
              <View style={styles.roleRow}>
                <RoleButton label="Cliente" active={role === 'customer'} onPress={() => setRole('customer')} />
                <RoleButton label="Estafeta" active={role === 'courier'} onPress={() => setRole('courier')} />
              </View>

              <Text style={styles.label}>Nome</Text>
              <TextInput
                style={styles.input}
                value={name}
                onChangeText={setName}
                placeholder={role === 'courier' ? 'Nome do estafeta' : 'Nome do cliente'}
                placeholderTextColor="#95a5c0"
              />
            </>
          ) : null}

          <Text style={styles.label}>Email</Text>
          <TextInput
            style={styles.input}
            autoCapitalize="none"
            keyboardType="email-address"
            value={email}
            onChangeText={setEmail}
            placeholder="cliente@fastbite.pt"
            placeholderTextColor="#95a5c0"
          />

          <Text style={styles.label}>Palavra-passe</Text>
          <View style={styles.passwordField}>
            <TextInput
              style={[styles.input, styles.passwordInput]}
              secureTextEntry={!showPassword}
              value={password}
              onChangeText={setPassword}
              placeholder="Palavra-passe"
              placeholderTextColor="#95a5c0"
            />
            <Pressable
              style={styles.passwordToggle}
              onPress={() => setShowPassword((current) => !current)}
              accessibilityRole="button"
              accessibilityLabel={showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'}
            >
              <Text style={styles.passwordToggleText}>{showPassword ? '\u25CC' : '\u{1F441}'}</Text>
            </Pressable>
          </View>

          {activeMode === 'login' ? (
            <Pressable style={styles.loginBtn} onPress={handleLogin} disabled={loadingAction !== ''}>
              <Text style={styles.loginBtnText}>{loadingAction === 'login' ? 'A entrar...' : 'Entrar'}</Text>
            </Pressable>
          ) : (
            <Pressable style={styles.loginBtn} onPress={handleRegister} disabled={loadingAction !== ''}>
              <Text style={styles.loginBtnText}>
                {loadingAction === 'register' ? 'A criar conta...' : 'Criar conta'}
              </Text>
            </Pressable>
          )}
        </View>
      </View>
    </SafeAreaView>
  )
}

function ModeButton({ label, active, onPress }) {
  return (
    <Pressable style={[styles.modeBtn, active ? styles.modeBtnActive : null]} onPress={onPress}>
      <Text style={[styles.modeBtnText, active ? styles.modeBtnTextActive : null]}>{label}</Text>
    </Pressable>
  )
}

function RoleButton({ label, active, onPress }) {
  return (
    <Pressable style={[styles.roleBtn, active ? styles.roleBtnActive : null]} onPress={onPress}>
      <Text style={[styles.roleBtnText, active ? styles.roleBtnTextActive : null]}>{label}</Text>
    </Pressable>
  )
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#f2f4f7',
  },
  container: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  brand: {
    color: '#2f6fe9',
    fontSize: 36,
    fontWeight: '900',
    textAlign: 'center',
  },
  subtitle: {
    marginTop: 8,
    textAlign: 'center',
    color: '#64748b',
    fontSize: 16,
  },
  roleRow: {
    marginTop: 14,
    flexDirection: 'row',
    gap: 8,
    justifyContent: 'center',
  },
  modeRow: {
    flexDirection: 'row',
    borderRadius: 12,
    backgroundColor: '#f1f5f9',
    padding: 4,
    marginBottom: 8,
    gap: 4,
  },
  modeBtn: {
    flex: 1,
    borderRadius: 9,
    paddingVertical: 10,
    alignItems: 'center',
  },
  modeBtnActive: {
    backgroundColor: '#3278ee',
  },
  modeBtnText: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '800',
  },
  modeBtnTextActive: {
    color: '#ffffff',
  },
  roleBtn: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 999,
    backgroundColor: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  roleBtnActive: {
    borderColor: '#3278ee',
    backgroundColor: '#eaf2ff',
  },
  roleBtnText: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '700',
  },
  roleBtnTextActive: {
    color: '#1d4ed8',
  },
  formCard: {
    marginTop: 20,
    borderWidth: 1,
    borderColor: '#e2e8f0',
    borderRadius: 16,
    backgroundColor: '#fff',
    padding: 16,
  },
  label: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 6,
    marginTop: 6,
  },
  input: {
    borderWidth: 1,
    borderColor: '#d5dce7',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    color: '#0f172a',
    fontSize: 15,
  },
  passwordField: {
    position: 'relative',
    justifyContent: 'center',
  },
  passwordInput: {
    paddingRight: 46,
  },
  passwordToggle: {
    position: 'absolute',
    right: 6,
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  passwordToggleText: {
    color: '#64748b',
    fontSize: 18,
    fontWeight: '800',
  },
  loginBtn: {
    marginTop: 16,
    borderRadius: 12,
    height: 46,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#3278ee',
  },
  loginBtnText: {
    color: '#fff',
    fontSize: 17,
    fontWeight: '800',
  },
  errorText: {
    marginTop: 10,
    color: '#b91c1c',
    fontSize: 13,
    fontWeight: '600',
  },
})

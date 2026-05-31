import { createContext, useCallback, useContext, useEffect, useState } from 'react'
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native'

const ToastContext = createContext(null)
const TOAST_DURATION_MS = 2500
let nextToastId = 0

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const dismiss = useCallback((id) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))
  }, [])

  const push = useCallback(
    ({ message, title, kind = 'info', duration = TOAST_DURATION_MS }) => {
      if (!message && !title) return null
      const id = (nextToastId += 1)
      setToasts((current) => [...current, { id, message, title, kind }])
      if (duration > 0) setTimeout(() => dismiss(id), duration)
      return id
    },
    [dismiss],
  )

  return (
    <ToastContext.Provider value={{ push, dismiss }}>
      {children}
      <Modal
        transparent
        visible={toasts.length > 0}
        animationType="none"
        statusBarTranslucent
        onRequestClose={() => dismiss(toasts[toasts.length - 1]?.id)}
      >
        <View pointerEvents="box-none" style={styles.stack}>
          {toasts.map((toast) => (
            <Pressable
              key={toast.id}
              onPress={() => dismiss(toast.id)}
              style={[styles.toast, toast.kind === 'error' ? styles.error : styles.success]}
            >
              {toast.title ? <Text style={styles.title}>{toast.title}</Text> : null}
              {toast.message ? <Text style={styles.message}>{toast.message}</Text> : null}
            </Pressable>
          ))}
        </View>
      </Modal>
    </ToastContext.Provider>
  )
}

export function useToast() {
  return useContext(ToastContext) ?? { push: () => null, dismiss: () => {} }
}

export function useAutoToast({ message, kind = 'info', duration = TOAST_DURATION_MS } = {}) {
  const { push } = useToast()

  useEffect(() => {
    if (!message) return
    push({ message, kind, duration })
  }, [message, kind, duration, push])
}

const styles = StyleSheet.create({
  stack: {
    position: 'absolute',
    top: 48,
    left: 16,
    right: 16,
    zIndex: 9999,
    gap: 8,
  },
  toast: {
    borderWidth: 1,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    shadowColor: '#0f172a',
    shadowOpacity: 0.16,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 8 },
    elevation: 8,
  },
  success: {
    backgroundColor: '#f0fdf4',
    borderColor: '#86efac',
  },
  error: {
    backgroundColor: '#fef2f2',
    borderColor: '#fecaca',
  },
  title: {
    color: '#0f172a',
    fontSize: 13,
    fontWeight: '800',
    marginBottom: 2,
  },
  message: {
    color: '#475569',
    fontSize: 13,
    fontWeight: '600',
  },
})

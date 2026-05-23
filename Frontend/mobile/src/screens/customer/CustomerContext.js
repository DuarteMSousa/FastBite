import { createContext, useContext } from 'react'

const CustomerContext = createContext(null)

export function CustomerProvider({ value, children }) {
  return <CustomerContext.Provider value={value}>{children}</CustomerContext.Provider>
}

export function useCustomer() {
  const context = useContext(CustomerContext)

  if (!context) {
    throw new Error('useCustomer must be used inside CustomerProvider')
  }

  return context
}

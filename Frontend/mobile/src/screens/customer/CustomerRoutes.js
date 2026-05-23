import { createNativeStackNavigator } from '@react-navigation/native-stack'
import { CartScreen } from './CartScreen'
import { HomeScreen } from './HomeScreen'
import { MenuScreen } from './MenuScreen'
import { OrdersHistoryScreen } from './OrdersHistoryScreen'
import { ProfileScreen } from './ProfileScreen'
import { TrackingScreen } from './TrackingScreen'
import { useCustomer } from './CustomerContext'

export const CUSTOMER_ROUTES = {
  HOME: 'home',
  PROFILE: 'profile',
  ORDERS: 'orders',
  MENU: 'menu',
  CART: 'cart',
  TRACKING: 'tracking',
}

const Stack = createNativeStackNavigator()

export function CustomerNavigator() {
  const { screens } = useCustomer()

  return (
    <Stack.Navigator
      initialRouteName={CUSTOMER_ROUTES.HOME}
      screenOptions={{
        headerShown: false,
        animation: 'slide_from_right',
      }}
    >
      <Stack.Screen name={CUSTOMER_ROUTES.HOME}>
        {() => <HomeScreen {...screens.home} />}
      </Stack.Screen>
      <Stack.Screen name={CUSTOMER_ROUTES.PROFILE}>
        {() => <ProfileScreen {...screens.profile} />}
      </Stack.Screen>
      <Stack.Screen name={CUSTOMER_ROUTES.ORDERS}>
        {() => <OrdersHistoryScreen {...screens.orders} />}
      </Stack.Screen>
      <Stack.Screen name={CUSTOMER_ROUTES.MENU}>
        {() => <MenuScreen {...screens.menu} />}
      </Stack.Screen>
      <Stack.Screen name={CUSTOMER_ROUTES.CART}>
        {() => <CartScreen {...screens.cart} />}
      </Stack.Screen>
      <Stack.Screen name={CUSTOMER_ROUTES.TRACKING}>
        {() => <TrackingScreen {...screens.tracking} />}
      </Stack.Screen>
    </Stack.Navigator>
  )
}

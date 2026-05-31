import { RestaurantOrdersQueueScreen } from './screens/RestaurantOrdersQueueScreen'
import { RestaurantVirtualKitchenScreen } from './screens/RestaurantVirtualKitchenScreen'
import { RestaurantMenuCatalogScreen } from './screens/RestaurantMenuCatalogScreen'
import { RestaurantChainCatalogScreen } from './screens/RestaurantChainCatalogScreen'
import { RestaurantChatScreen } from './screens/RestaurantChatScreen'
import { RestaurantNotificationsScreen } from './screens/RestaurantNotificationsScreen'
import { RestaurantOrderDetailScreen } from './screens/RestaurantOrderDetailScreen'
import { RestaurantOrdersHistoryScreen } from './screens/RestaurantOrdersHistoryScreen'
import { RestaurantReviewsScreen } from './screens/RestaurantReviewsScreen'
import { RestaurantCampaignsScreen } from './screens/RestaurantCampaignsScreen'
import { RestaurantProfileScreen } from './screens/RestaurantProfileScreen'

export const RESTAURANT_VIEWS = [
  {
    id: 'dashboard',
    path: 'dashboard',
    label: 'Dashboard',
    icon: 'DB',
    Component: RestaurantOrdersQueueScreen,
  },
  {
    id: 'kitchen',
    path: 'kitchen',
    label: 'Cozinha virtual',
    icon: 'KV',
    Component: RestaurantVirtualKitchenScreen,
  },
  {
    id: 'history',
    path: 'history',
    label: 'Histórico',
    icon: 'HS',
    Component: RestaurantOrdersHistoryScreen,
  },
  {
    id: 'order-detail',
    path: 'orders/detail',
    label: 'Detalhe pedido',
    icon: 'DP',
    Component: RestaurantOrderDetailScreen,
    hideFromNav: true,
  },
  {
    id: 'chain-catalog',
    path: 'chain-catalog',
    label: 'Catálogo da cadeia',
    icon: 'CC',
    Component: RestaurantChainCatalogScreen,
    chainOnly: true,
  },
  {
    id: 'menu',
    path: 'menu',
    label: 'Menu do restaurante',
    icon: 'GM',
    Component: RestaurantMenuCatalogScreen,
  },
  {
    id: 'reviews',
    path: 'reviews',
    label: 'Avaliações',
    icon: 'AV',
    Component: RestaurantReviewsScreen,
  },
  {
    id: 'campaigns',
    path: 'campaigns',
    label: 'Campanhas',
    icon: 'CP',
    Component: RestaurantCampaignsScreen,
    chainOnly: true,
  },
  {
    id: 'profile',
    path: 'profile',
    label: 'Perfil',
    icon: 'PF',
    Component: RestaurantProfileScreen,
    hideFromNav: true,
  },
  {
    id: 'chat',
    path: 'chat',
    label: 'Chat',
    icon: 'CH',
    Component: RestaurantChatScreen,
  },
  {
    id: 'notifications',
    path: 'notifications',
    label: 'Notificações',
    icon: 'NT',
    Component: RestaurantNotificationsScreen,
  },
]

export function viewPath(viewId) {
  return RESTAURANT_VIEWS.find((view) => view.id === viewId)?.path ?? RESTAURANT_VIEWS[0].path
}

export function viewByPath(pathname) {
  const normalized = String(pathname ?? '').replace(/^\/+|\/+$/g, '')
  return RESTAURANT_VIEWS.find((view) => view.path === normalized) ?? null
}

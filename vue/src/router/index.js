import { createRouter, createWebHistory } from 'vue-router'
import InventoryView from '../views/InventoryView.vue'
import LoginView from '../views/LoginView.vue'

/**
 * Vue Router configuration
 * 
 * Defines all application routes, including nested inventory routes for modal state persistence.
 * Also handles dynamic page title updates based on route metadata.
 */
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      redirect: '/inventory'
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { title: 'Login' }
    },
    {
      path: '/about',
      name: 'about',
      component: () => import('../views/AboutView.vue'),
      meta: { title: 'About' }
    },
    {
      path: '/inventory',
      name: 'inventory',
      component: InventoryView,
      meta: { title: 'Inventory' },
      children: [
        {
          path: 'new',
          name: 'inventory-new',
          component: InventoryView,
          meta: { title: 'New Item' }
        },
        {
          path: 'edit/:id',
          name: 'inventory-edit',
          component: InventoryView,
          meta: { title: 'Edit Item' }
        }
      ]
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { title: 'Profile' }
    },
    {
      path: '/vendors',
      name: 'vendors',
      component: () => import('../views/VendorsView.vue'),
      meta: { title: 'Vendors' }
    },
    {
      path: '/customers',
      name: 'customers',
      component: () => import('../views/CustomersView.vue'),
      meta: { title: 'Customers' }
    },
    {
      path: '/storage-locations',
      name: 'storage-locations',
      component: () => import('../views/StorageLocationsView.vue'),
      meta: { title: 'Storage Locations' }
    }
  ]
})

/**
 * Global afterEach guard
 * 
 * Updates the document title based on the meta title of the target route.
 */
router.afterEach((to) => {
  const title = to.meta.title
  document.title = title ? `${title} | Pintventory` : 'Pintventory'
})

export default router

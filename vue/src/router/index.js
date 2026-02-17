import { createRouter, createWebHistory } from 'vue-router'
import InventoryView from '../views/InventoryView.vue'
import LoginView from '../views/LoginView.vue'

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
      component: LoginView
    },
    {
      path: '/inventory',
      name: 'inventory',
      component: InventoryView
    },
    // Placeholder routes for now
    {
      path: '/vendors',
      name: 'vendors',
      component: () => import('../views/VendorsView.vue')
    },
    {
      path: '/customers',
      name: 'customers',
      component: () => import('../views/CustomersView.vue')
    }
  ]
})

export default router

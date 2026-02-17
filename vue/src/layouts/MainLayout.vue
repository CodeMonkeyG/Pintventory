<script setup>
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const logout = async () => {
  await authStore.logout();
  router.push('/login');
};
</script>

<template>
  <div class="layout">
    <aside class="sidebar">
      <div class="logo">Thriftly</div>
      <nav>
        <router-link to="/inventory" class="nav-item">Inventory</router-link>
        <router-link to="/customers" class="nav-item">Customers</router-link>
        <router-link to="/vendors" class="nav-item">Vendors</router-link>
        <router-link to="/profile" class="nav-item">Profile</router-link>
      </nav>
      <div class="user-info">
        <div v-if="authStore.user" class="user-details">
            <img :src="authStore.user.avatar" alt="Avatar" class="avatar" v-if="authStore.user.avatar">
            <span>{{ authStore.user.name }}</span>
        </div>
        <button @click="logout" class="logout-btn">Logout</button>
      </div>
    </aside>
    <main class="content">
      <slot></slot>
    </main>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  height: 100vh;
}

.sidebar {
  width: 250px;
  display: flex;
  flex-direction: column;
  padding: 20px;
}

.logo {
  font-size: 24px;
  font-weight: bold;
  margin-bottom: 40px;
}

.nav-item {
  text-decoration: none;
  padding: 10px;
  margin-bottom: 5px;
  border-radius: 4px;
  transition: background 0.2s;
}

.nav-item:hover, .nav-item.router-link-active {
  /* No color on hover */
}

.user-info {
  margin-top: auto;
  padding-top: 20px;
}

.user-details {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  margin-right: 10px;
}

.logout-btn {
  background: none;
  border: 1px solid black; /* Keep a border structure but default color */
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
  width: 100%;
}

.logout-btn:hover {
  /* No hover color */
}

.content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}
</style>

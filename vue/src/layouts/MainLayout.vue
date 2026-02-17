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
    <header class="navbar">
      <div class="logo">Thriftly</div>
      
      <nav class="nav-links">
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
    </header>
    
    <main class="content">
      <slot></slot>
    </main>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #ddd; /* Light separator */
}

.logo {
  font-size: 24px;
  font-weight: bold;
}

.nav-links {
  display: flex;
  gap: 20px;
}

.nav-item {
  text-decoration: none;
  padding: 5px 10px;
  border-radius: 4px;
}

.nav-item.router-link-active {
  font-weight: bold;
  text-decoration: underline;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 15px;
}

.user-details {
  display: flex;
  align-items: center;
  gap: 10px;
}

.avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
}

.logout-btn {
  background: none;
  border: 1px solid black;
  padding: 5px 10px;
  border-radius: 4px;
  cursor: pointer;
}

.content {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}
</style>
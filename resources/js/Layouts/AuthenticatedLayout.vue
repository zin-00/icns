<script setup>
import { ref, toRefs } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { BsUpcScan } from '@kalimahapps/vue-icons';
import { route } from 'ziggy-js';
import axios from 'axios';
import { useReactiveStore } from '../store/reactives/reactive';
import Loader from '../Components/loader/Loader.vue';
import { BxSolidNavigation } from '@kalimahapps/vue-icons';

const reactive = useReactiveStore();
const { isLoading } = toRefs(reactive);

const message = ref('Processing logout...');
const showingNavigationDropdown = ref(false);

const logout = async () => {
  try {
    isLoading.value = true;
    message.value = 'Processing logout...';

    const response = await axios.post(route('logout'), {}, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });

    message.value = response.data.message || 'Logged out successfully. Redirecting to login page...';

    setTimeout(() => {
      window.location.href = route('login');
    }, 2000);

  } catch (error) {
    console.error('Logout error:', error);
    message.value = 'An error occurred during logout. Please try again.';
    setTimeout(() => {
      isLoading.value = false;
    }, 2000);
  }
};
</script>

<template>
  <div>
    <div class="min-h-screen bg-gray-100">
      <nav class="border-b border-gray-100 bg-white">
        <!-- Primary Navigation Menu -->
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div class="flex h-16 justify-between">
            <div class="flex">
              <!-- Logo -->
              <div class="flex shrink-0 items-center">

                <Link :href="route('dashboard')" class="flex items-center gap-2">
                   <BxSolidNavigation class=" h-10 w-10 text-black"/>
                  <span class="text-xl font-semibold text-gray-800">ASSCAT NAVIGATOR</span>
                </Link>
              </div>

              <!-- Navigation Links -->
              <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <NavLink :href="route('reports.dashboard')" :active="route().current('reports.dashboard')">
                  Overview
                </NavLink>
                <NavLink :href="route('facility.get')" :active="route().current('facility.get')">
                  Facilities
                </NavLink>
                <NavLink :href="route('feedback.index')" :active="route().current('feedback.index')">
                  Feedbacks
                </NavLink>
                <NavLink :href="route('notes.index')" :active="route().current('notes.index')">
                  Notes
                </NavLink>
                <NavLink :href="route('map.get')" :active="route().current('map.get')">
                  Campus Map
                </NavLink>
                <NavLink :href="route('logs.index')" :active="route().current('logs.index')">
                    Search Logs
                </NavLink>
              </div>
            </div>

            <div class="hidden sm:ms-6 sm:flex sm:items-center">
              <!-- Settings Dropdown -->
              <div class="relative ms-3">
                <Dropdown align="right" width="48">
                  <template #trigger>
                    <span class="inline-flex rounded-md">
                      <button
                        type="button"
                        class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                      >
                        {{ $page.props.auth.user.name }}

                        <svg
                          class="-me-0.5 ms-2 h-4 w-4"
                          xmlns="http://www.w3.org/2000/svg"
                          viewBox="0 0 20 20"
                          fill="currentColor"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                          />
                        </svg>
                      </button>
                    </span>
                  </template>

                  <template #content>
                    <DropdownLink> Profile </DropdownLink>
                    <DropdownLink @click="logout"> Log Out </DropdownLink>
                  </template>
                </Dropdown>
              </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
              <button
                @click="showingNavigationDropdown = !showingNavigationDropdown"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
              >
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                  <path
                    :class="{
                      hidden: showingNavigationDropdown,
                      'inline-flex': !showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                  <path
                    :class="{
                      hidden: !showingNavigationDropdown,
                      'inline-flex': showingNavigationDropdown,
                    }"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div
          :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }"
          class="sm:hidden"
        >
          <div class="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
              Dashboard
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('facility.get')" :active="route().current('facility.get')">
              Facilities
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('feedback.index')" :active="route().current('feedback.index')">
              Feedbacks
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('notes.index')" :active="route().current('notes.index')">
              Notes
            </ResponsiveNavLink>
            <ResponsiveNavLink :href="route('map.get')" :active="route().current('map.get')">
              Campus Map
            </ResponsiveNavLink>
          </div>

          <!-- Responsive Settings Options -->
          <div class="border-t border-gray-200 pb-1 pt-4">
            <div class="px-4">
              <div class="text-base font-medium text-gray-800">
                {{ $page.props.auth.user.name }}
              </div>
              <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
            </div>

            <div class="mt-3 space-y-1">
              <ResponsiveNavLink> Profile </ResponsiveNavLink>
              <ResponsiveNavLink @click="logout"> Log Out </ResponsiveNavLink>
            </div>
          </div>
        </div>
      </nav>

      <!-- Page Heading -->
      <header v-if="$slots.header" class="bg-white shadow">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <slot name="header" />
        </div>
      </header>

      <!-- Page Content -->
      <main>
        <slot />
      </main>
    </div>

    <Loader :is-loading="isLoading" :message="message" />
  </div>
</template>

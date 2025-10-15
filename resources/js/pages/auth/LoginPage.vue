<script setup>
import { ref } from 'vue'
import { useToast } from 'vue-toastification'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { BxSolidNavigation } from '@kalimahapps/vue-icons';
import { route } from 'ziggy-js';
import {LoaderCircle } from 'lucide-vue-next';

const toast = useToast()

const step = ref(1)
const email = ref('')
const password = ref('')
const remember = ref(false)
const processing = ref(false)
const errors = ref({})

// Check email existence
const checkEmail = async () => {
  errors.value = {}
  processing.value = true
  try {
    const res = await axios.post('/auth/check-email', { email: email.value })

    if (res.data.exists) {
      step.value = 2
    } else {
      errors.value.email = 'Email not found'
    }
  } catch (error) {
    console.error(error)
    toast.error('Error checking email')
  } finally {
    processing.value = false
  }
}

// Login function
const login = async () => {
  errors.value = {}
  processing.value = true

  try {
    const res = await axios.post('/login', {
      email: email.value,
      password: password.value,
      remember: remember.value
    })
    router.visit(route('reports.dashboard'))
    toast.success(res.data.message || 'Login successful!')
  } catch (error) {
    console.error('Login failed:', error)

    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    }

    toast.error('Login failed. Please check your credentials.')
  } finally {
    processing.value = false
  }
}
const goToRequestAccess = () => {
  router.visit(route('register.get'))
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-200">
    <div
      class="w-full max-w-[800px] bg-white shadow-md rounded-lg grid grid-cols-1 lg:grid-cols-2 overflow-hidden"
    >
      <!-- Left Side - Branding (only visible on large screens) -->
      <div
        class="hidden lg:flex flex-col items-start justify-start bg-white p-20 text-center"
      >
        <div class="flex justify-center mb-6">
           <BxSolidNavigation class=" h-20 w-20 text-black"/>
        </div>
        <div class="text-left mb-8">
          <h1 class="text-2xl font-normal text-gray-900 mb-1">Sign in</h1>
          <p class="text-sm text-gray-600">to continue to Campus Navigator</p>
        </div>
      </div>

      <!-- Right Side - Login Form -->
      <div class="flex items-center justify-center p-10">
        <div class="w-full max-w-md">
          <!-- Branding (visible only on small screens) -->
          <div class="flex flex-col items-center mb-8 lg:hidden">
                <BxSolidNavigation class=" h-10 w-10 text-black"/>
            <h1 class="text-xl font-normal text-gray-900">Sign in</h1>
            <p class="text-sm text-gray-600">to continue to Campus Navigator</p>
          </div>

          <!-- Form -->
          <form
            @submit.prevent="step === 1 ? checkEmail() : login()"
            class="space-y-6"
          >
            <!-- Step 1: Email -->
            <div v-if="step === 1">
              <input
                id="email"
                type="email"
                required
                autofocus
                v-model="email"
                placeholder="Email or phone"
                class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-gray-500 text-sm"
                :class="{ 'border-red-500': errors.email }"
              />
              <div
                v-if="errors.email"
                class="text-red-600 text-sm mt-2"
              >
                {{ errors.email }}
              </div>

              <div class="mt-2">
                <a href="#" class="text-blue-600 text-sm hover:underline"
                  >Forgot email?</a
                >
              </div>

              <div class="flex items-center justify-between mt-8">
                <button
                  type="button"
                  @click="goToRequestAccess"
                  class="text-blue-600 font-medium text-sm hover:bg-blue-50 px-3 py-1.5 rounded"
                >
                  Register
                </button>

                <button
                  type="submit"
                  class="bg-gray-700 hover:bg-gray-600 cursor-pointer text-white px-6 py-2 rounded-4xl font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  :disabled="processing"
                >
                  <LoaderCircle
                    v-if="processing"
                    class="h-4 w-4 animate-spin"
                  />
                  Next
                </button>
              </div>
            </div>

            <!-- Step 2: Password -->
            <div v-else>
              <!-- Email display -->
              <div class="flex items-center space-x-3 mb-6">
                <div
                  class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium"
                >
                  {{ email.charAt(0).toUpperCase() }}
                </div>
                <div class="flex-1">
                  <div class="text-sm font-medium text-gray-900">
                    {{ email }}
                  </div>
                </div>
                <button
                  type="button"
                  @click="step = 1"
                  class="text-blue-600 hover:underline text-xs font-medium"
                >
                  Change
                </button>
              </div>

              <input
                id="password"
                type="password"
                required
                v-model="password"
                placeholder="Enter your password"
                class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm"
                :class="{ 'border-red-500': errors.password }"
              />
              <div
                v-if="errors.password"
                class="text-red-600 text-sm mt-2"
              >
                {{ errors.password }}
              </div>

              <div class="flex items-center mt-4">
                <input
                  type="checkbox"
                  id="remember"
                  v-model="remember"
                  class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                />
                <label for="remember" class="ml-2 text-sm text-gray-600">
                  Remember me
                </label>
              </div>

              <div class="flex items-center justify-between mt-8">
                <button
                  type="button"
                  @click="step = 1"
                  class="text-blue-600 font-medium text-sm hover:bg-blue-50 px-3 py-1.5 rounded"
                >
                  Back
                </button>

                <button
                  type="submit"
                  class="bg-gray-700 hover:bg-gray-600 text-white px-6 py-2 rounded-[100px] font-medium text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                  :disabled="processing"
                >
                  <LoaderCircle
                    v-if="processing"
                    class="h-4 w-4 animate-spin"
                  />
                  Sign in
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

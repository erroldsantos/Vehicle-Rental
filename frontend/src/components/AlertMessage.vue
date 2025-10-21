<template>
  <transition name="alert">
    <div v-if="visible && message" :class="['alert', `alert-${type}`]" role="alert">
      <div class="alert-content">
        <div class="alert-icon">
          <i :class="alertIcon"></i>
        </div>
        <div class="alert-body">
          <h4 v-if="title" class="alert-title">{{ title }}</h4>
          <p class="alert-message">{{ message }}</p>
        </div>
        <button v-if="dismissible" @click="dismiss" class="alert-close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      
      <div v-if="autoClose && progress" class="alert-progress">
        <div class="alert-progress-bar" :style="{ width: progress + '%' }"></div>
      </div>
    </div>
  </transition>
</template>

<script>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'

export default {
  name: 'AlertMessage',
  props: {
    message: {
      type: String,
      required: true
    },
    type: {
      type: String,
      default: 'info',
      validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    title: {
      type: String,
      default: ''
    },
    autoClose: {
      type: Boolean,
      default: true
    },
    duration: {
      type: Number,
      default: 5000
    },
    dismissible: {
      type: Boolean,
      default: true
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const visible = ref(true)
    const progress = ref(100)
    let timer = null
    let progressTimer = null
    
    const alertIcon = computed(() => {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
      }
      return icons[props.type]
    })
    
    const dismiss = () => {
      visible.value = false
      emit('close')
      clearTimers()
    }
    
    const clearTimers = () => {
      if (timer) {
        clearTimeout(timer)
        timer = null
      }
      if (progressTimer) {
        clearInterval(progressTimer)
        progressTimer = null
      }
    }
    
    const startAutoClose = () => {
      if (!props.autoClose) return
      
      timer = setTimeout(() => {
        dismiss()
      }, props.duration)
      
      // Progress bar animation
      const interval = 50
      const step = (interval / props.duration) * 100
      
      progressTimer = setInterval(() => {
        progress.value -= step
        if (progress.value <= 0) {
          progress.value = 0
          clearInterval(progressTimer)
        }
      }, interval)
    }
    
    onMounted(() => {
      startAutoClose()
    })
    
    onBeforeUnmount(() => {
      clearTimers()
    })
    
    watch(() => props.message, () => {
      if (props.message) {
        visible.value = true
        progress.value = 100
        clearTimers()
        startAutoClose()
      }
    })
    
    return {
      visible,
      progress,
      alertIcon,
      dismiss
    }
  }
}
</script>
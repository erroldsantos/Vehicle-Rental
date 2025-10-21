<template>
  <div class="stats-card" :class="variant">
    <div class="stats-content">
      <div class="stats-header">
        <div class="stats-icon">
          <i :class="icon"></i>
        </div>
        <div class="stats-trend" v-if="trend">
          <i :class="trend > 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
          <span>{{ Math.abs(trend) }}%</span>
        </div>
      </div>
      
      <div class="stats-body">
        <h3 class="stats-value">{{ formattedValue }}</h3>
        <p class="stats-label">{{ title }}</p>
      </div>
      
      <div class="stats-footer" v-if="subtitle">
        <span class="stats-subtitle">{{ subtitle }}</span>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'

export default {
  name: 'StatsCard',
  props: {
    title: {
      type: String,
      required: true
    },
    value: {
      type: [String, Number],
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'success', 'warning', 'danger', 'info'].includes(value)
    },
    trend: {
      type: Number,
      default: null
    },
    subtitle: {
      type: String,
      default: ''
    }
  },
  setup(props) {
    const formattedValue = computed(() => {
      if (typeof props.value === 'number') {
        return props.value.toLocaleString()
      }
      return props.value
    })
    
    return {
      formattedValue
    }
  }
}
</script>
<template>
  <BaseCard clipped class="cursor-pointer hover:bg-slate-50" @click="emit('click')">
    <div class="p-4">
      <h3 class="text-sm font-semibold leading-tight">
        {{ heraldNotification.title }}
      </h3>

      <p v-if="heraldNotification.description" class="text-sm text-slate-600 leading-tight mt-1">
        {{ heraldNotification.description }}
      </p>
    </div>
  </BaseCard>
</template>

<script lang="ts" setup>
import { HeraldNotification } from "../models/HeraldNotification";
const emit = defineEmits(["click"]);

defineProps<{
  heraldNotification: HeraldNotification;
}>();
</script>

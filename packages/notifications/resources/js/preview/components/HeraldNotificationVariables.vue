<template>
  <div class="border border-slate-300 rounded bg-slate-100">
    <div class="text-xs border-b border-b-slate-300 px-3 font-semibold py-2.5">Variables</div>
    <div class="block text-xs text-slate-500 p-3">
      <BaseJsonReader
        class="bg-transparent text-xs text-black p-0"
        :model-value="heraldNotification.variables"
        variant="collapse"
      />
    </div>
  </div>
</template>

<script lang="ts" setup>
import { BaseJsonReader } from "sprintify-ui";
import { HeraldNotificationDetails } from "../models/HeraldNotification";

defineProps<{
  heraldNotification: HeraldNotificationDetails;
}>();
</script>

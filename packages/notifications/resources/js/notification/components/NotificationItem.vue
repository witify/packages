<template>
  <div class="relative block w-full rounded-md border border-slate-300 bg-white px-4 py-3 shadow">
    <div class="flex justify-between">
      <button
        type="button"
        class="relative w-full grow text-left"
        @click="openNotification(notification)"
      >
        <div
          class="mb-1 flex gap-1 text-sm text-slate-900"
          :class="[notification.read_at ? '' : 'font-semibold']"
        >
          <div
            v-if="notification.read_at === null"
            class="relative top-1 mr-1 h-2 w-2 shrink-0 rounded-full bg-red-500"
          />
          <div v-safe-html="notification.text" />
        </div>
        <div class="text-xs text-slate-500">
          {{ isoStringToHuman(notification.created_at) }}
        </div>
      </button>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { DateTime } from "luxon";
import { useAppNotificationsStore } from "../stores/notifications";
import { AppNotification } from "../../notification/models/AppNotification";

defineProps({
  notification: {
    required: true,
    type: Object as PropType<AppNotification>,
  },
});

const router = useRouter();

const appNotificationsStore = useAppNotificationsStore();

function isoStringToHuman(dateTime: string) {
  return DateTime.fromISO(dateTime).toRelative();
}

async function openNotification(notification: AppNotification) {
  if (notification.path) {
    router.push(notification.path);
  }
  await appNotificationsStore.markAsRead(notification.id);
}
</script>

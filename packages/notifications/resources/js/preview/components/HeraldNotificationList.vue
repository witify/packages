<template>
  <div>
    <div class="relative min-h-[300px]">
      <BaseLoadingCover
        :model-value="loading"
        class="z-10"
        size="lg"
        tw-backdrop="bg-beige-100 bg-opacity-50"
      />

      <div class="space-y-6">
        <div v-for="(notifications, group) in groupedNotifications" :key="group">
          <h3
            v-if="group"
            class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400"
          >
            {{ group }}
          </h3>

          <ul class="space-y-3">
            <HeraldNotificationItem
              v-for="heraldNotification in notifications"
              :key="heraldNotification.class"
              :herald-notification="heraldNotification"
              @click="showHeraldNotification(heraldNotification)"
            />
          </ul>
        </div>
      </div>
    </div>

    <BaseModalCenter v-model="showHeraldNotificationModal" max-width="900px">
      <HeraldNotificationShow
        v-if="activeHeraldNotification && showHeraldNotificationModal"
        :herald-notification-class="activeHeraldNotification.class"
      />
    </BaseModalCenter>
  </div>
</template>

<script lang="ts" setup>
import { useHttp } from "../../services";
import { HeraldNotification } from "../models/HeraldNotification";
import HeraldNotificationItem from "../components/HeraldNotificationItem.vue";
import HeraldNotificationShow from "./HeraldNotificationShow.vue";

const http = useHttp();
const i18n = useI18n();

useHead({
  title: i18n.t("modules.notification_preview.notification_previews"),
});

const loading = ref(false);
const heraldNotifications = ref([] as HeraldNotification[]);

function fetch() {
  loading.value = true;

  http
    .get(window.route("api.herald_notifications.index"))
    .then((response) => {
      heraldNotifications.value = response.data.data;
    })
    .finally(() => {
      loading.value = false;
    });
}

fetch();

const groupedNotifications = computed(() => {
  const groups: Record<string, HeraldNotification[]> = {};

  for (const notification of heraldNotifications.value) {
    const group = notification.group || "";

    if (!groups[group]) {
      groups[group] = [];
    }

    groups[group].push(notification);
  }

  return groups;
});

const showHeraldNotificationModal = ref(false);
const activeHeraldNotification = ref<HeraldNotification | null>(null);

function showHeraldNotification(heraldNotification: HeraldNotification) {
  activeHeraldNotification.value = heraldNotification;
  showHeraldNotificationModal.value = true;
}
</script>

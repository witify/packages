<template>
  <HeraldNotificationList />
</template>

<script lang="ts" setup>
import HeraldNotificationList from "../components/HeraldNotificationList.vue";

const i18n = useI18n();

useHead({
  title: i18n.t("modules.notification_preview.notification_previews"),
});
</script>

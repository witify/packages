<template>
  <div class="py-6">
    <BaseContainer>
      <BaseHeader :title="$t('notifications')" class="mb-8" />
      <NotificationList />
    </BaseContainer>
  </div>
</template>

<script lang="ts" setup>
import NotificationList from "../components/NotificationList.vue";
</script>

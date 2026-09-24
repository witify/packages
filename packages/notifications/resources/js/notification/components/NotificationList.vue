<template>
  <div class="relative">
    <BaseDataIterator
      ref="dataIteratorRef"
      :searchable="false"
      history-mode
      :url="$laravelRoute('api.notifications.index')"
    >
      <template #default="{ items, firstLoad }">
        <NotificationItem
          v-for="notification in items"
          :key="notification.id"
          class="mb-2"
          :notification="notification"
        />

        <BaseCard v-if="firstLoad && items.length == 0">
          <BaseCardRow>
            <div class="p-5">
              <p class="text-center">
                {{ $t("no_notification") }}
              </p>
            </div>
          </BaseCardRow>
        </BaseCard>
      </template>
    </BaseDataIterator>
  </div>
</template>

<script lang="ts" setup>
import { Ref } from "vue";
import { BaseDataIterator } from "sprintify-ui";
import NotificationItem from "./NotificationItem.vue";

const dataIteratorRef = ref(null) as Ref<InstanceType<typeof BaseDataIterator> | null>;

function fetch() {
  dataIteratorRef.value?.fetch();
}

fetch();

provide("fetch", fetch);
</script>

<template>
  <div class="border border-black/15 rounded-lg shadow overflow-hidden relative">
    <div v-if="mail">
      <div class="p-3 border-b">
        <p class="leading-tight text-sm font-semibold">
          <span class="font-semibold"> {{ $t("modules.notification_preview.subject") }}: </span>
          {{ mail.subject }}
        </p>
      </div>
      <!-- sandbox (no tokens) blocks scripts while keeping the mail's own styles -->
      <iframe :srcdoc="mail.html" sandbox="" class="w-full h-[36rem] bg-white" :title="mail.subject" />
    </div>
    <div v-else class="py-16 px-4 text-center text-sm text-gray-500">
      {{ $t("modules.notification_preview.no_preview") }}
    </div>

    <BaseLoadingCover :model-value="loading" size="lg" />
  </div>
</template>

<script lang="ts" setup>
import { MailPreview } from "../models/MailPreview";

defineProps<{
  mail: MailPreview | null;
  loading?: boolean;
}>();
</script>

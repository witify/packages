<template>
  <div class="relative overflow-hidden rounded-lg border border-black/15 bg-white shadow">
    <div v-if="database?.text">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
        <p class="text-sm font-semibold leading-tight text-slate-900">
          {{ $t("modules.notification_preview.database_preview") }}
        </p>
      </div>

      <div class="p-4">
        <div
          class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-800"
        >
          <div v-safe-html="database.text" />
        </div>

        <dl
          v-if="database.path || database.url"
          class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-white p-3 text-xs"
        >
          <div v-if="database.path" class="grid gap-1 sm:grid-cols-[7rem_minmax(0,1fr)]">
            <dt class="font-medium text-slate-500">
              {{ $t("modules.notification_preview.path") }}
            </dt>
            <dd class="min-w-0 break-words text-slate-800">
              {{ database.path }}
            </dd>
          </div>

          <div v-if="database.url" class="grid gap-1 sm:grid-cols-[7rem_minmax(0,1fr)]">
            <dt class="font-medium text-slate-500">
              {{ $t("modules.notification_preview.url") }}
            </dt>
            <dd class="min-w-0 break-words text-slate-800">
              {{ database.url }}
            </dd>
          </div>
        </dl>
      </div>
    </div>

    <div v-else class="px-4 py-16 text-center text-sm text-gray-500">
      {{ $t("modules.notification_preview.no_preview") }}
    </div>
  </div>
</template>

<script lang="ts" setup>
import { DatabasePreview } from "../models/DatabasePreview";

defineProps<{
  database: DatabasePreview | null;
}>();
</script>

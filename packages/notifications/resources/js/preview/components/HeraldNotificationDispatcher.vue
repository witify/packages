<template>
  <div v-if="heraldNotificationDispatch" class="relative">
    <div v-if="heraldNotificationDispatch.notifiables.length">
      <div class="flex px-5 py-3 items-center justify-between">
        <div class="font-semibold">
          {{ heraldNotificationDispatch.herald_notification.title }}
        </div>

        <BaseSelect v-model="locale" required size="sm">
          <option v-for="code in locales" :key="code" :value="code">
            {{ $laravel.app.locales[code] ?? code }}
          </option>
        </BaseSelect>
      </div>

      <div>
        <div class="mx-5">
          <BaseTabs v-model="tab" size="md" class="-mb-px">
            <BaseTabItem id="preview">
              {{ $t("preview") }}
            </BaseTabItem>
            <BaseTabItem id="edit">
              {{ $t("edit") }}
            </BaseTabItem>
          </BaseTabs>
        </div>
      </div>

      <div v-if="tab == 'preview'" class="p-4">
        <BaseAlert v-if="hasErrors" color="danger" class="mb-4">
          {{ $t("modules.notification_preview.preview_error") }}
        </BaseAlert>

        <HeraldNotificationMailPreview v-if="preview" :mail="preview" :loading="fetching" />

        <div v-if="missingNotificationMessageFields" class="mt-5">
          <BaseAlert color="danger" bordered>
            {{ $t("modules.notification_preview.missing_fields") }}
          </BaseAlert>
        </div>

        <BaseForm
          v-if="heraldNotificationDispatch"
          v-slot="{ disabled, loading }"
          class="mt-5"
          :url="props.url"
          :method="props.method"
          :data="dataInternal"
          @success="emit('success')"
        >
          <!-- checkbox for selected notifiables-->

          <div>
            <p class="font-semibold text-sm mb-4">
              {{ $t("modules.notification_preview.send_to") }} :
            </p>
            <ul>
              <li
                v-for="notifiable in heraldNotificationDispatch.notifiables"
                :key="notifiable.id"
                class="mb-2"
              >
                <label class="inline-flex">
                  <input
                    v-model="selectedNotifiables"
                    type="checkbox"
                    class="border-slate-400 rounded mt-1"
                    :value="notifiable.id"
                  />
                  <div class="ml-3 text-sm">
                    <p class="font-medium text-sm leading-tight">
                      {{ notifiable.full_name }}
                    </p>
                    <small class="text-sm text-gray-500 leading-tight">
                      {{ notifiable.email }}
                    </small>
                  </div>
                </label>
              </li>
            </ul>
          </div>

          <BaseButton
            :disabled="disabled || missingNotificationMessageFields"
            type="submit"
            class="mt-4"
            color="primary"
            size="md"
            icon-position="end"
            icon="heroicons:paper-airplane-16-solid"
            :loading="loading"
          >
            {{ $t("send") }}
          </BaseButton>
        </BaseForm>
      </div>

      <div v-if="tab == 'edit'" class="p-4">
        <HeraldNotificationMessageFormPartial
          v-for="(notificationMessage, i) in notificationMessages"
          v-show="locale == notificationMessage.locale"
          :key="notificationMessage.locale"
          v-model="notificationMessages[i]"
          :herald-notification="heraldNotificationDispatch.herald_notification"
        />
      </div>
    </div>
    <div v-else class="p-5">
      <BaseAlert color="warning" bordered>
        {{ $t("modules.notification_preview.no_notifiables") }}
      </BaseAlert>

      <BaseButton type="button" class="mt-4" @click="emit('success')">
        {{ $t("close") }}
      </BaseButton>
    </div>
  </div>
  <div v-else class="p-16 relative">
    <BaseLoadingCover v-model="fetching" size="lg" tw-backdrop="bg-slate-50 bg-opacity-50" />
  </div>
</template>

<script lang="ts" setup>
import { useHttp } from "../../services";
import HeraldNotificationMessageFormPartial from "./HeraldNotificationMessageFormPartial.vue";
import { NotificationMessage } from "../models/NotificationMessage";
import { HeraldNotificationDispatch } from "../models/HeraldNotificationDispatch";
import { MailPreview } from "../models/MailPreview";
import { uniq } from "lodash";
import HeraldNotificationMailPreview from "./HeraldNotificationMailPreview.vue";

const emit = defineEmits<{
  (e: "success"): void;
}>();

const props = defineProps<{
  url: string;
  method: "post" | "put" | "patch";
  data?: Record<string, unknown>;
}>();

const locale = ref("en");
const tab = ref("preview");
const fetching = ref(false);
const heraldNotificationDispatch = ref<HeraldNotificationDispatch | null>(null);
const notificationMessages = ref<NotificationMessage[]>([]);
const selectedNotifiables = ref<string[]>([]);
const hasErrors = ref(false);

const missingNotificationMessageFields = computed(() => {
  return notificationMessages.value.some((message) => {
    return message.subject === "" || message.message === "";
  });
});

const notificationMessagesFilled = computed(() => {
  return notificationMessages.value.some((message) => {
    return message.subject !== "" || message.message !== "";
  });
});

const locales = computed<string[]>(() => {
  if (heraldNotificationDispatch.value?.notifiables.length) {
    return uniq(heraldNotificationDispatch.value.notifiables.map((n) => n.locale));
  }

  return Object.keys(window.Laravel.app.locales);
});

/**
 * Custom notification messages is only sent if at least one message is filled
 */
const dataInternal = computed(() => {
  const data = {
    notification_messages: [] as NotificationMessage[],
    notifiable_ids: selectedNotifiables.value,
  };

  if (notificationMessagesFilled.value) {
    data.notification_messages = notificationMessages.value;
  }

  return {
    ...(props.data ?? {}),
    ...data,
  };
});

const preview = computed<MailPreview | null>(() => {
  if (!heraldNotificationDispatch.value) {
    return null;
  }

  return heraldNotificationDispatch.value.previews[locale.value] ?? null;
});

function fetchPreview() {
  fetching.value = true;

  const route = props.url;

  hasErrors.value = false;

  useHttp()
    .post(route, { ...dataInternal.value, action: "preview" })
    .then((response: any) => {
      heraldNotificationDispatch.value = response.data.data;

      Object.keys(window.Laravel.app.locales).forEach((code) => {
        const alreadyExists = notificationMessages.value.some((message) => message.locale === code);

        if (alreadyExists) {
          return;
        }

        const defaultNotificationMessage =
          heraldNotificationDispatch.value?.herald_notification.notification_messages.find(
            (message) => message.locale === code,
          );

        notificationMessages.value.push({
          notification_class: heraldNotificationDispatch.value?.herald_notification.class ?? "",
          subject: defaultNotificationMessage?.subject ?? "",
          message: defaultNotificationMessage?.message ?? "",
          channel: "mail",
          locale: code,
        });
      });

      nextTick(() => {
        if (!locales.value.includes(locale.value)) {
          locale.value = locales.value[0] || "en";
        }

        if (selectedNotifiables.value.length === 0 && heraldNotificationDispatch.value) {
          selectedNotifiables.value = heraldNotificationDispatch.value.notifiables.map((n) => n.id);
        }
      });
    })
    .catch(() => {
      hasErrors.value = true;
    })
    .finally(() => {
      fetching.value = false;
    });
}

fetchPreview();

watch(
  () => tab.value,
  () => {
    if (tab.value == "preview") {
      fetchPreview();
    }
  },
);
</script>

<template>
  <div v-if="!heraldNotification" class="relative overflow-hidden rounded-xl p-20">
    <BaseLoadingCover />
  </div>

  <div v-else class="overflow-hidden">
    <div class="border-b border-slate-200 px-5 pt-4">
      <div class="flex flex-col gap-1 pb-4">
        <div class="text-lg font-semibold leading-6 text-slate-950">
          {{ heraldNotification.title }}
        </div>
        <p v-if="heraldNotification.description" class="text-sm leading-5 text-slate-500">
          {{ heraldNotification.description }}
        </p>
      </div>

      <BaseTabs v-model="tab" size="md" class="-mb-px">
        <BaseTabItem id="previews">
          {{ $t("previews") }}
        </BaseTabItem>
        <BaseTabItem v-if="heraldNotification.customizable" id="edit">
          {{ $t("edit") }}
        </BaseTabItem>
      </BaseTabs>
    </div>

    <div v-if="tab === 'previews'" class="space-y-5 p-5">
      <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
        <div class="grid gap-3 sm:grid-cols-2">
          <BaseField :label="$t('locale')" name="locale" size="sm">
            <BaseSelect v-model="locale" required size="sm" class="w-full">
              <option v-for="(label, code) in $laravel.app.locales" :key="code" :value="code">
                {{ label }}
              </option>
            </BaseSelect>
          </BaseField>

          <BaseField :label="$t('modules.notification_preview.channel')" name="channel" size="sm">
            <BaseSelect v-model="selectedChannel" required size="sm" class="w-full">
              <option v-for="channel in supportedChannels" :key="channel" :value="channel">
                {{ channelLabel(channel) }}
              </option>
            </BaseSelect>
          </BaseField>
        </div>
      </div>

      <ul v-if="previews.length > 0" class="space-y-5">
        <li v-for="(preview, index) in previews" :key="`${selectedChannel}.${locale}.${index}`">
          <div class="mb-2 flex items-center justify-between gap-3">
            <p class="min-w-0 truncate text-sm font-semibold leading-5 text-slate-900">
              {{ preview.title }}
            </p>
            <span
              class="shrink-0 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-xs font-medium text-slate-600"
            >
              {{ channelLabel(selectedChannel) }}
            </span>
          </div>

          <HeraldNotificationMailPreview
            v-if="selectedChannel === CHANNEL_MAIL"
            :mail="preview.mail"
          />
          <HeraldNotificationDatabasePreview v-else :database="preview.database" />
        </li>
      </ul>

      <div
        v-else
        class="rounded-lg border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500"
      >
        {{ $t("modules.notification_preview.no_preview") }}
      </div>
    </div>

    <div v-if="tab === 'edit'" class="p-5">
      <BaseForm
        :url="$laravelRoute('api.notification_messages.update.batch')"
        method="patch"
        :data="formData"
        @success="fetch"
      >
        <div class="space-y-5">
          <HeraldNotificationVariables :herald-notification="heraldNotification" />

          <div class="space-y-4">
            <section
              v-for="notificationMessageGroup in notificationMessageGroups"
              :key="notificationMessageGroup.channel"
              class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
            >
              <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                <p class="text-sm font-semibold leading-5 text-slate-900">
                  {{ channelLabel(notificationMessageGroup.channel) }}
                </p>
              </div>

              <div class="divide-y divide-slate-200">
                <div
                  v-for="localeGroup in notificationMessageGroup.localeGroups"
                  :key="
                    notificationMessageKey(
                      form.notification_messages[localeGroup.notificationMessageIndex],
                    )
                  "
                  class="p-4"
                >
                  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-medium leading-5 text-slate-700">
                      {{ localeLabel(localeGroup.locale) }}
                    </p>

                    <BaseSwitch
                      :model-value="
                        form.notification_messages[localeGroup.notificationMessageIndex].customized
                      "
                      size="xs"
                      @update:model-value="
                        onUpdateCustomized(localeGroup.notificationMessageIndex, Boolean($event))
                      "
                    >
                      <span class="text-xs">
                        {{ $t("modules.notification_preview.customize_section") }}
                      </span>
                    </BaseSwitch>
                  </div>

                  <div
                    :class="{
                      'opacity-60':
                        !form.notification_messages[localeGroup.notificationMessageIndex]
                          .customized,
                    }"
                  >
                    <HeraldNotificationMessageFormPartial
                      v-model="form.notification_messages[localeGroup.notificationMessageIndex]"
                      :herald-notification="heraldNotification"
                      :show-variables="false"
                      :disabled="
                        !form.notification_messages[localeGroup.notificationMessageIndex].customized
                      "
                    />
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>

        <div v-if="missingNotificationMessageFields" class="mt-5">
          <BaseAlert color="danger" bordered>
            {{ $t("modules.notification_preview.missing_fields") }}
          </BaseAlert>
        </div>

        <div class="mt-6 flex flex-wrap gap-2">
          <BaseButton
            :disabled="missingNotificationMessageFields"
            type="submit"
            color="primary"
            icon="heroicons:check-16-solid"
          >
            {{ $t("save") }}
          </BaseButton>

          <BaseButton
            type="button"
            icon="heroicons:trash-16-solid"
            @click="deleteNotificationMessageConfirm"
          >
            {{ $t("delete") }}
          </BaseButton>
        </div>
      </BaseForm>
    </div>
  </div>
</template>

<script lang="ts" setup>
import { useHttp } from "../../services";
import { useDialogsStore } from "sprintify-ui";
import HeraldNotificationDatabasePreview from "./HeraldNotificationDatabasePreview.vue";
import HeraldNotificationMailPreview from "./HeraldNotificationMailPreview.vue";
import HeraldNotificationMessageFormPartial from "./HeraldNotificationMessageFormPartial.vue";
import HeraldNotificationVariables from "./HeraldNotificationVariables.vue";
import { HeraldNotificationDetails } from "../models/HeraldNotification";
import { NotificationMessage } from "../models/NotificationMessage";
import { NotificationPreview } from "../models/NotificationPreview";
import {
  createNotificationMessageFormState,
  isBlankNotificationMessageValue,
  updateNotificationMessageCustomized,
} from "../services/notificationMessageCustomization";

const CHANNEL_MAIL = "mail";
const CHANNEL_DATABASE = "database";

const i18n = useI18n();

const props = defineProps<{
  heraldNotificationClass: string;
}>();

interface NotificationMessageLocaleGroup {
  locale: string;
  notificationMessageIndex: number;
}

interface NotificationMessageGroup {
  channel: string;
  localeGroups: NotificationMessageLocaleGroup[];
}

const tab = ref<"previews" | "edit">("previews");
const locale = ref(window.Laravel.app.locale);
const selectedChannel = ref(CHANNEL_MAIL);
const localeCodes = Object.keys(window.Laravel.app.locales);

const form = ref<{
  notification_messages: NotificationMessage[];
}>({
  notification_messages: [],
});

const heraldNotification = ref<HeraldNotificationDetails | null>(null);

const supportedChannels = computed(() => {
  if (!heraldNotification.value?.supported_channels.length) {
    return [CHANNEL_MAIL];
  }

  return heraldNotification.value.supported_channels;
});

const formData = computed(() => {
  return {
    notification_messages: form.value.notification_messages.map((notificationMessage) => ({
      notification_class: notificationMessage.notification_class,
      locale: notificationMessage.locale,
      channel: notificationMessage.channel,
      subject: notificationMessage.subject,
      message: notificationMessage.message,
      customized: notificationMessage.customized ?? false,
    })),
  };
});

const notificationMessageGroups = computed<NotificationMessageGroup[]>(() => {
  return supportedChannels.value
    .map((channel) => {
      return {
        channel,
        localeGroups: localeCodes
          .map((localeCode) => {
            const index = form.value.notification_messages.findIndex((notificationMessage) => {
              return (
                notificationMessage.locale === localeCode && notificationMessage.channel === channel
              );
            });

            return index !== -1 ? { locale: localeCode, notificationMessageIndex: index } : null;
          })
          .filter((group): group is NotificationMessageLocaleGroup => group !== null),
      };
    })
    .filter((group) => group.localeGroups.length > 0);
});

const missingNotificationMessageFields = computed(() => {
  return form.value.notification_messages.some((message) => {
    if (!message.customized) {
      return false;
    }

    if (isBlank(message.message)) {
      return true;
    }

    return message.channel === CHANNEL_MAIL && isBlank(message.subject);
  });
});

const previews = computed<NotificationPreview[]>(() => {
  if (!heraldNotification.value?.previews) {
    return [];
  }

  return (heraldNotification.value.previews[locale.value] ?? []).filter((preview) => {
    if (selectedChannel.value === CHANNEL_MAIL) {
      return preview.mail !== null;
    }

    return preview.database !== null;
  });
});

function fetch(): void {
  const route = window.route("api.herald_notifications.show", {
    notification_class: props.heraldNotificationClass,
  });

  useHttp()
    .post(route)
    .then((response) => {
      const notification = response.data.data as HeraldNotificationDetails;

      heraldNotification.value = notification;
      form.value.notification_messages = buildNotificationMessages(notification);

      syncSelectedChannel();
    });
}

function buildNotificationMessages(notification: HeraldNotificationDetails): NotificationMessage[] {
  return supportedChannelsFor(notification).flatMap((channel) => {
    return localeCodes.map((localeCode) => {
      const notificationMessage = notification.notification_messages.find((message) => {
        return message.locale === localeCode && message.channel === channel;
      });

      return createNotificationMessageFormState({
        notificationClass: notification.class,
        locale: localeCode,
        channel,
        notificationMessage,
      });
    });
  });
}

function supportedChannelsFor(notification: HeraldNotificationDetails): string[] {
  if (!notification.supported_channels.length) {
    return [CHANNEL_MAIL];
  }

  return notification.supported_channels;
}

function syncSelectedChannel(): void {
  if (supportedChannels.value.includes(selectedChannel.value)) {
    return;
  }

  selectedChannel.value = supportedChannels.value[0] ?? CHANNEL_MAIL;
}

function channelLabel(channel: string): string {
  const labels: Record<string, string> = {
    [CHANNEL_MAIL]: "modules.notification_preview.mail_channel",
    [CHANNEL_DATABASE]: "modules.notification_preview.database_channel",
  };

  return i18n.t(labels[channel] ?? channel);
}

function localeLabel(localeCode: string): string {
  return window.Laravel.app.locales[localeCode] ?? localeCode;
}

function notificationMessageKey(notificationMessage: NotificationMessage): string {
  return `${notificationMessage.channel}.${notificationMessage.locale}`;
}

function onUpdateCustomized(index: number, customized: boolean): void {
  const notificationMessage = form.value.notification_messages[index];

  if (!notificationMessage) {
    return;
  }

  form.value.notification_messages[index] = updateNotificationMessageCustomized(
    notificationMessage,
    customized,
  );
}

function isBlank(value: string | undefined): boolean {
  return isBlankNotificationMessageValue(value);
}

watch(
  () => props.heraldNotificationClass,
  () => {
    fetch();
  },
  { immediate: true },
);

function deleteNotificationMessageConfirm(): void {
  useDialogsStore().push({
    title: i18n.t("modules.notification_preview.delete_notification_message"),
    message: i18n.t("modules.notification_preview.delete_notification_message_confirm"),
    confirmText: i18n.t("delete"),
    color: "danger",
    onConfirm: () => {
      deleteNotificationMessage();
    },
  });
}

function deleteNotificationMessage(): void {
  useHttp()
    .delete(
      window.route("api.notification_messages.destroy", {
        notification_class: heraldNotification.value?.class ?? props.heraldNotificationClass,
      }),
    )
    .then(() => {
      fetch();
      tab.value = "previews";
    });
}
</script>

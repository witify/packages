<template>
  <div>
    <div class="space-y-4">
      <BaseField
        v-if="requiresSubject"
        :label="$t('modules.notification_preview.subject')"
        :required="!disabled"
        :name="`${modelValue.channel}.${modelValue.locale}.subject`"
      >
        <BaseInput
          :model-value="modelValue.subject"
          class="w-full"
          :disabled="disabled"
          @update:model-value="emit('update:modelValue', { ...modelValue, subject: $event })"
        />
      </BaseField>

      <BaseField
        :label="messageLabel"
        :required="!disabled"
        :name="`${modelValue.channel}.${modelValue.locale}.message`"
      >
        <BaseRichText
          :model-value="modelValue.message"
          :toolbar="toolbar"
          :disabled="disabled"
          @update:model-value="emit('update:modelValue', { ...modelValue, message: $event })"
        />
      </BaseField>
    </div>

    <HeraldNotificationVariables
      v-if="showVariables"
      :herald-notification="heraldNotification"
      class="mt-6"
    />
  </div>
</template>

<script lang="ts" setup>
import { HeraldNotificationDetails } from "../models/HeraldNotification";
import { NotificationMessage } from "../models/NotificationMessage";
import HeraldNotificationVariables from "./HeraldNotificationVariables.vue";

const CHANNEL_MAIL = "mail";
const CHANNEL_DATABASE = "database";

const i18n = useI18n();

const props = withDefaults(
  defineProps<{
    heraldNotification: HeraldNotificationDetails;
    modelValue: NotificationMessage;
    showVariables?: boolean;
    disabled?: boolean;
  }>(),
  {
    showVariables: true,
    disabled: false,
  },
);

const requiresSubject = computed(() => props.modelValue.channel === CHANNEL_MAIL);

const messageLabel = computed(() => {
  if (props.modelValue.channel === CHANNEL_DATABASE) {
    return i18n.t("modules.notification_preview.database_message");
  }

  return i18n.t("modules.notification_preview.message");
});

const toolbar = computed(() => {
  if (props.modelValue.channel === CHANNEL_DATABASE) {
    return ["bold", "italic", "underline", "link"];
  }

  return ["bold", "italic", "underline", "link", "orderedList", "bulletList"];
});

const emit = defineEmits<{
  (e: "update:modelValue", value: NotificationMessage): void;
}>();
</script>

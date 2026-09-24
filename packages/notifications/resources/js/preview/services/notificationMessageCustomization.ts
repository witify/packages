import { NotificationMessage } from "../models/NotificationMessage";

interface NotificationMessageFormStateParameters {
  notificationClass: string;
  locale: string;
  channel: string;
  notificationMessage?: NotificationMessage;
}

export function createNotificationMessageFormState({
  notificationClass,
  locale,
  channel,
  notificationMessage,
}: NotificationMessageFormStateParameters): NotificationMessage {
  const defaultSubject = notificationMessage?.default_subject ?? notificationMessage?.subject ?? "";
  const defaultMessage = notificationMessage?.default_message ?? notificationMessage?.message ?? "";

  return {
    notification_class: notificationClass,
    locale,
    channel,
    subject: notificationMessage?.subject ?? "",
    message: notificationMessage?.message ?? "",
    customized: notificationMessage?.customized ?? false,
    default_subject: defaultSubject,
    default_message: defaultMessage,
    custom_subject: notificationMessage?.customized ? notificationMessage.subject : defaultSubject,
    custom_message: notificationMessage?.customized ? notificationMessage.message : defaultMessage,
  };
}

export function updateNotificationMessageCustomized(
  notificationMessage: NotificationMessage,
  customized: boolean,
): NotificationMessage {
  const defaultSubject = notificationMessage.default_subject ?? notificationMessage.subject;
  const defaultMessage = notificationMessage.default_message ?? notificationMessage.message;
  const customSubject = resolveCustomDraftValue(notificationMessage.custom_subject, defaultSubject);
  const customMessage = resolveCustomDraftValue(notificationMessage.custom_message, defaultMessage);

  return {
    ...notificationMessage,
    customized,
    custom_subject: customized ? customSubject : notificationMessage.subject,
    custom_message: customized ? customMessage : notificationMessage.message,
    subject: customized ? customSubject : defaultSubject,
    message: customized ? customMessage : defaultMessage,
  };
}

function resolveCustomDraftValue(customValue: string | undefined, defaultValue: string): string {
  if (customValue === undefined || isBlankNotificationMessageValue(customValue)) {
    return defaultValue;
  }

  return customValue;
}

export function isBlankNotificationMessageValue(value: string | undefined): boolean {
  return (
    (value ?? "")
      .replace(/<[^>]*>/g, "")
      .replace(/&nbsp;/g, " ")
      .trim() === ""
  );
}

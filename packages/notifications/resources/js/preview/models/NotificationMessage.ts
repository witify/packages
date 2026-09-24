export interface NotificationMessage {
  notification_class: string;
  locale: string;
  channel: string;
  subject: string;
  message: string;
  customized?: boolean;
  default_subject?: string;
  default_message?: string;
  custom_subject?: string;
  custom_message?: string;
}

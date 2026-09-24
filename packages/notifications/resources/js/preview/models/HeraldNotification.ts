import { NotificationMessage } from "./NotificationMessage";
import { NotificationPreview } from "./NotificationPreview";

export interface HeraldNotification {
  key: string;
  class: string;
  title: string;
  description: string | null;
  group: string;
  toggleable: boolean;
  customizable: boolean;
  supported_channels: string[];
}

export interface HeraldNotificationDetails extends HeraldNotification {
  notification_messages: NotificationMessage[];
  previews: {
    [locale: string]: NotificationPreview[];
  };
  variables: Record<string, unknown>;
}

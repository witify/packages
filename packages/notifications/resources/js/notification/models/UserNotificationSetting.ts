export interface NotificationChannels {
  mail: boolean;
  database: boolean;
}

export interface UserNotificationSetting {
  key: string;
  class: string;
  title: string;
  description: string | null;
  group: string;
  channels: NotificationChannels;
  supported_channels: (keyof NotificationChannels)[];
}

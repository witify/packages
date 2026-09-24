interface AppNotification {
  id: string;
  text: string;
  notifiable_id: string;
  notifiable_type: string;
  model_id?: number;
  model_type?: string;
  read_at: string | null;
  path: string | null;
  url: string | null;
  created_at: string;
}

export { AppNotification };

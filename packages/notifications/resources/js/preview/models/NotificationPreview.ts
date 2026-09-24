import { DatabasePreview } from "./DatabasePreview";
import { MailPreview } from "./MailPreview";

export interface NotificationPreview {
  title: string;
  mail: MailPreview | null;
  database: DatabasePreview | null;
}

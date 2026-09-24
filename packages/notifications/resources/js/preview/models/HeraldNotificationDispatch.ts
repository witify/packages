import { HeraldNotificationDetails } from "./HeraldNotification";
import { MailPreview } from "./MailPreview";
import { HeraldNotifiable } from "./HeraldNotifiable";

export interface HeraldNotificationDispatch {
  herald_notification: HeraldNotificationDetails;
  notifiables: HeraldNotifiable[];
  previews: Record<string, MailPreview>;
}

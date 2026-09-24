import { RouteRecordRaw } from "vue-router";
import routes from "./routes";

/**
 * The notification previews module. The page itself is mounted by the host
 * router under its settings (route `settings.notification_previews`).
 */
export const notificationPreviewModule = {
  name(): string {
    return "notification_preview";
  },

  menuSection(): undefined {
    return undefined;
  },

  routes(): RouteRecordRaw[] {
    return routes;
  },

  init(): Promise<void> {
    return Promise.resolve();
  },
};

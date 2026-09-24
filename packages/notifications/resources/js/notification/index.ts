import { RouteRecordRaw } from "vue-router";
import routes from "./routes";
import { currentUserId, echo } from "../services";
import { useAppNotificationsStore } from "./stores/notifications";

/**
 * The inbox module: same shape as the Module class of the host, so the host
 * registers it with its own modules.
 */
export const notificationModule = {
  name(): string {
    return "notification";
  },

  menuSection(): undefined {
    return undefined;
  },

  routes(): RouteRecordRaw[] {
    return routes;
  },

  async init(): Promise<void> {
    const userId = currentUserId();

    if (userId === null) {
      return;
    }

    const store = useAppNotificationsStore();
    await store.fetch();

    echo()
      ?.private("user." + userId)
      .listen(".notifications.created", () => {
        store.fetch();
      });
  },

  onNotificationClick(notification: { id: string }): void {
    useAppNotificationsStore().markAsRead(notification.id);
  },

  onNotificationOpen(): void {
    useAppNotificationsStore().fetch();
  },
};

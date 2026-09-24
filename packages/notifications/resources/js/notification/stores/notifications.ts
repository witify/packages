import { defineStore } from "pinia";
import { AppNotification } from "../models/AppNotification";
import { http } from "../../services";
import { Notification } from "sprintify-ui";

export const useAppNotificationsStore = defineStore("appNotifications", {
  state: () => {
    return {
      notifications: [] as AppNotification[],
    };
  },
  getters: {
    layoutNotifications(state) {
      return state.notifications.map((n) => {
        return {
          id: n.id,
          text: n.text,
          created_at: n.created_at,
          to: n.path,
        } as Notification;
      });
    },
  },
  actions: {
    set(notifications: AppNotification[]) {
      this.notifications = notifications;
    },
    fetch() {
      http.get(window.route("api.notifications.index", { filter: { read: false } })).then((response) => {
        this.notifications = response.data.data;
      });
    },
    async markAsRead(notificationId: string) {
      this.notifications = this.notifications.filter((n) => n.id != notificationId);

      await http.patch(
        window.route("api.notifications.update", {
          notification: notificationId,
        }),
        {
          read: true,
        },
      );
    },
  },
});

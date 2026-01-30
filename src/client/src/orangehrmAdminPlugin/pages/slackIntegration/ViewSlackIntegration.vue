<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('admin.slack_integration') }}
      </oxd-text>
      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <!-- Enable/Disable Toggle -->
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-group :label="$t('admin.enable_slack_digest')">
                <oxd-switch-input v-model="slackConfig.enabled" />
              </oxd-input-group>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <!-- Webhook URL -->
        <oxd-form-row>
          <oxd-grid :cols="1" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="slackConfig.webhookUrl"
                :label="$t('admin.webhook_url')"
                :placeholder="$t('admin.webhook_url_placeholder')"
                :rules="rules.webhookUrl"
                :disabled="!slackConfig.enabled"
                required
              />
              <oxd-text class="orangehrm-input-hint" tag="p">
                {{ $t('admin.webhook_url_hint') }}
              </oxd-text>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <!-- Digest Time and Timezone -->
        <oxd-form-row>
          <oxd-grid :cols="2" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="slackConfig.digestTime"
                :label="$t('admin.digest_time')"
                :placeholder="$t('admin.digest_time_placeholder')"
                :rules="rules.digestTime"
                :disabled="!slackConfig.enabled"
                required
              />
            </oxd-grid-item>
            <oxd-grid-item>
              <oxd-input-field
                v-model="slackConfig.timezone"
                type="select"
                :label="$t('admin.timezone')"
                :options="timezoneOptions"
                :disabled="!slackConfig.enabled"
                required
              />
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <!-- Leave Types Multi-select -->
        <oxd-form-row>
          <oxd-grid :cols="1" class="orangehrm-full-width-grid">
            <oxd-grid-item>
              <oxd-input-field
                v-model="slackConfig.leaveTypes"
                type="select"
                :label="$t('admin.leave_types')"
                :options="leaveTypeOptions"
                :disabled="!slackConfig.enabled"
                :multiple="true"
              />
              <oxd-text class="orangehrm-input-hint" tag="p">
                {{ $t('admin.leave_types_hint') }}
              </oxd-text>
            </oxd-grid-item>
          </oxd-grid>
        </oxd-form-row>

        <oxd-divider />

        <!-- Test Message Button -->
        <oxd-form-row v-if="slackConfig.enabled && slackConfig.webhookUrl">
          <oxd-button
            display-type="secondary"
            :label="$t('admin.send_test_message')"
            @click="onTestMessage"
            :loading="isTesting"
          />
        </oxd-form-row>

        <oxd-divider />

        <!-- Form Actions -->
        <oxd-form-actions>
          <required-text />
          <oxd-button
            display-type="ghost"
            :label="$t('general.reset')"
            @click="onReset"
          />
          <submit-button />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {APIService} from '@ohrm/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';

export default {
  name: 'ViewSlackIntegration',

  props: {
    leaveTypes: {
      type: Array,
      default: () => [],
    },
    timezones: {
      type: Array,
      default: () => [],
    },
  },

  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/admin/slack-integration',
    );
    return {
      http,
    };
  },

  data() {
    return {
      isLoading: false,
      isTesting: false,
      slackConfig: {
        enabled: false,
        webhookUrl: '',
        digestTime: '09:00',
        timezone: 'UTC',
        leaveTypes: [],
      },
      initialConfig: {},
      rules: {
        webhookUrl: [
          required,
          shouldNotExceedCharLength(500),
          (v) => {
            if (!v) return true;
            if (!v.startsWith('https://hooks.slack.com/')) {
              return 'Must be a valid Slack webhook URL';
            }
            return true;
          },
        ],
        digestTime: [
          required,
          (v) => {
            if (!v) return true;
            // Validate HH:MM format
            const timeRegex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
            if (!timeRegex.test(v)) {
              return 'Must be in HH:MM format (e.g., 09:00)';
            }
            return true;
          },
        ],
      },
    };
  },

  computed: {
    timezoneOptions() {
      return this.timezones.map((tz) => ({
        id: tz,
        label: tz,
      }));
    },
    leaveTypeOptions() {
      return this.leaveTypes;
    },
  },

  created() {
    this.loadData();
  },

  methods: {
    loadData() {
      this.isLoading = true;
      this.http
        .request({
          method: 'GET',
        })
        .then((response) => {
          const {data} = response.data;
          this.slackConfig = {
            enabled: data.enabled || false,
            webhookUrl: data.webhookUrl || '',
            digestTime: data.digestTime || '09:00',
            timezone: data.timezone || 'UTC',
            leaveTypes: data.leaveTypes || [],
          };
          this.initialConfig = {...this.slackConfig};
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    onSave() {
      this.isLoading = true;
      this.http
        .request({
          method: 'PUT',
          data: {
            enabled: this.slackConfig.enabled,
            webhookUrl: this.slackConfig.webhookUrl,
            digestTime: this.slackConfig.digestTime,
            timezone: this.slackConfig.timezone,
            leaveTypes: this.slackConfig.leaveTypes,
          },
        })
        .then(() => {
          this.$toast.saveSuccess();
          this.loadData(); // Reload to get masked webhook URL
        })
        .finally(() => {
          this.isLoading = false;
        });
    },

    onReset() {
      this.slackConfig = {...this.initialConfig};
    },

    onTestMessage() {
      if (!this.slackConfig.webhookUrl) {
        this.$toast.error({
          title: 'Error',
          message: 'Please enter a webhook URL first',
        });
        return;
      }

      this.isTesting = true;
      const testHttp = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/admin/slack-integration-test',
      );
      
      testHttp
        .request({
          method: 'POST',
          data: {
            webhookUrl: this.slackConfig.webhookUrl,
          },
        })
        .then((response) => {
          const {data} = response.data;
          if (data.success) {
            this.$toast.success({
              title: 'Success',
              message: data.message || 'Test message sent successfully!',
            });
          } else {
            this.$toast.error({
              title: 'Error',
              message: data.message || 'Failed to send test message',
            });
          }
        })
        .catch(() => {
          this.$toast.error({
            title: 'Error',
            message: 'Failed to send test message. Please check your webhook URL.',
          });
        })
        .finally(() => {
          this.isTesting = false;
        });
    },
  },
};
</script>

<style src="./slack-integration.scss" lang="scss" scoped></style>

<template>
  <AuthLayout>
    <template #title>Connexion</template>
    <template #subtitle>Accédez à votre espace de gestion</template>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Alert for errors -->
      <Alert v-if="form.errors.email" variant="danger" dismissible @dismiss="form.clearErrors('email')">
        {{ form.errors.email }}
      </Alert>

      <!-- Email -->
      <Input
        v-model="form.email"
        type="email"
        label="Email"
        placeholder="votre@email.com"
        required
        :error="form.errors.email"
      />

      <!-- Password -->
      <Input
        v-model="form.password"
        type="password"
        label="Mot de passe"
        placeholder="••••••••"
        required
        :error="form.errors.password"
      />

      <!-- Remember me -->
      <div class="flex items-center justify-between">
        <Checkbox v-model="form.remember" label="Se souvenir de moi" />
      </div>

      <!-- Submit -->
      <Button
        type="submit"
        variant="primary"
        size="lg"
        full-width
        :loading="form.processing"
      >
        Se connecter
      </Button>
    </form>
  </AuthLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { AuthLayout, Input, Button, Checkbox, Alert } from '@/Components';

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

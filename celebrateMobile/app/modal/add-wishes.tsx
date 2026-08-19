import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, View } from 'react-native';

import { ApiError } from '../../src/api/client';
import { wishes as wishesApi } from '../../src/api/endpoints';
import { Sheet } from '../../src/components/Sheet';
import { Button, Field, Flash, IconButton, Txt } from '../../src/components/ui';
import { colors, radii, spacing } from '../../src/theme';

type Draft = { key: string; name: string; amount: string; description: string; imageUri?: string };

const blank = (): Draft => ({
  key: `${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
  name: '',
  amount: '',
  description: '',
});

/**
 * Add registry items — the web's wishlist form.
 *
 * Batches several items into one request, which is what the API's `wishlist`
 * array expects.
 */
export default function AddWishes() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const params = useLocalSearchParams<{ slug: string; id: string }>();

  const [drafts, setDrafts] = useState<Draft[]>([blank()]);
  const [flash, setFlash] = useState<string | null>(null);

  function update(key: string, patch: Partial<Draft>) {
    setDrafts((list) => list.map((d) => (d.key === key ? { ...d, ...patch } : d)));
  }

  async function pickImage(key: string) {
    const res = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });

    if (!res.canceled && res.assets[0]) {
      update(key, { imageUri: res.assets[0].uri });
    }
  }

  const save = useMutation({
    mutationFn: () =>
      wishesApi.create(
        Number(params.id),
        drafts
          .filter((d) => d.name.trim())
          .map((d) => ({
            name: d.name.trim(),
            amount: d.amount.trim() || undefined,
            description: d.description.trim() || undefined,
            imageUri: d.imageUri,
          })),
      ),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['celebration', params.slug] });
      router.back();
    },
    onError: (e) => setFlash(e instanceof ApiError ? e.message : 'Could not save the registry items.'),
  });

  const ready = drafts.some((d) => d.name.trim());

  return (
    <Sheet
      title="Add to your registry"
      sub="Name each thing you'd love. Give it a price and guests can chip in towards it."
      footer={
        <Button
          title={`Save ${drafts.filter((d) => d.name.trim()).length || ''} item${
            drafts.filter((d) => d.name.trim()).length === 1 ? '' : 's'
          }`.replace('  ', ' ')}
          full
          disabled={!ready}
          loading={save.isPending}
          onPress={() => {
            setFlash(null);
            save.mutate();
          }}
        />
      }
    >
      {flash ? <Flash kind="error" message={flash} /> : null}

      {drafts.map((draft, index) => (
        <View key={draft.key} style={styles.block}>
          <View style={styles.blockHead}>
            <Txt variant="tiny" color={colors.muted}>
              ITEM {index + 1}
            </Txt>
            {drafts.length > 1 ? (
              <IconButton
                icon="close"
                accessibilityLabel={`Remove item ${index + 1}`}
                onPress={() => setDrafts((list) => list.filter((d) => d.key !== draft.key))}
              />
            ) : null}
          </View>

          <Field
            label="What is it?"
            value={draft.name}
            onChangeText={(t) => update(draft.key, { name: t })}
            placeholder="Stand mixer"
          />

          <Field
            label="Price (optional)"
            value={draft.amount}
            onChangeText={(t) => update(draft.key, { amount: t.replace(/[^0-9.]/g, '') })}
            keyboardType="decimal-pad"
            placeholder="0.00"
            hint="Leave blank if it's not a money goal."
          />

          <Field
            label="Note (optional)"
            value={draft.description}
            onChangeText={(t) => update(draft.key, { description: t })}
            placeholder="Colour, size, where to find it…"
          />

          <Pressable onPress={() => pickImage(draft.key)} style={styles.imagePicker}>
            {draft.imageUri ? (
              <Image source={{ uri: draft.imageUri }} style={styles.thumb} contentFit="cover" />
            ) : (
              <View style={[styles.thumb, styles.thumbEmpty]}>
                <MaterialCommunityIcons name="image-plus" size={20} color={colors.primary} />
              </View>
            )}
            <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
              {draft.imageUri ? 'Change photo' : 'Add a photo'}
            </Txt>
          </Pressable>
        </View>
      ))}

      <Pressable onPress={() => setDrafts((list) => [...list, blank()])} style={styles.addRow}>
        <MaterialCommunityIcons name="plus" size={17} color={colors.primary} />
        <Txt variant="small" color={colors.primary} style={{ fontWeight: '700' }}>
          Add another item
        </Txt>
      </Pressable>
    </Sheet>
  );
}

const styles = StyleSheet.create({
  block: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.md,
    marginBottom: spacing.md,
  },
  blockHead: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: spacing.md,
  },
  imagePicker: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.md,
  },
  thumb: {
    width: 46,
    height: 46,
    borderRadius: radii.soft.sm,
    overflow: 'hidden',
  },
  thumbEmpty: {
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: colors.primaryFaint,
  },
  addRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingVertical: spacing.md,
    borderWidth: 1,
    borderStyle: 'dashed',
    borderColor: colors.primaryLight,
    borderRadius: radii.card,
  },
});

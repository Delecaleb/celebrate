import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import { Alert, Pressable, StyleSheet, Switch, View } from 'react-native';

import { ApiError } from '../../api/client';
import { celebrations } from '../../api/endpoints';
import type { CelebrationPage } from '../../api/types';
import { dateOnly } from '../../lib/format';
import { celebrationType, colors, radii, spacing } from '../../theme';
import { Button, Field, Flash, SectionHeader, Txt } from '../ui';

const STATUSES = ['draft', 'published', 'closed'] as const;

/**
 * Settings — owner only.
 *
 * The web app has no separate edit screen: page details live in this tab and save
 * through celebrant.update. Same here, plus the cover-photo picker, the custom
 * link and the theme picker.
 */
export function SettingsTab({ page, slug }: { page: CelebrationPage; slug: string }) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const c = page.celebration.data;

  const [title, setTitle] = useState(c.title);
  const [celebrantName, setCelebrantName] = useState(c.celebrant_name ?? '');
  const [type, setType] = useState(c.celebration_type ?? 'other');
  const [description, setDescription] = useState(c.description ?? '');
  const [venue, setVenue] = useState(c.venue ?? '');
  const [startDate, setStartDate] = useState(dateOnly(c.start_date));
  const [endDate, setEndDate] = useState(dateOnly(c.end_date));
  const [isPublic, setIsPublic] = useState(c.is_public);
  const [status, setStatus] = useState<string>(c.status ?? 'draft');
  const [flash, setFlash] = useState<{ kind: 'ok' | 'error'; message: string } | null>(null);

  // ── Custom link ──
  const [linkSlug, setLinkSlug] = useState(c.slug);
  const [linkCheck, setLinkCheck] = useState<{ available: boolean; reason: string | null } | null>(null);

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['celebration', slug] });
    queryClient.invalidateQueries({ queryKey: ['dashboard'] });
  };

  const save = useMutation({
    mutationFn: () =>
      celebrations.update(slug, {
        title: title.trim(),
        celebrant_name: celebrantName.trim(),
        celebration_type: type,
        description: description.trim() || null,
        venue: venue.trim() || null,
        start_date: startDate || null,
        end_date: endDate || null,
        is_public: isPublic,
        status,
      }),
    onSuccess: () => {
      setFlash({ kind: 'ok', message: 'Page details saved.' });
      invalidate();
    },
    onError: (e) => setFlash({ kind: 'error', message: e instanceof ApiError ? e.message : 'Could not save.' }),
  });

  const uploadCovers = useMutation({
    mutationFn: (uris: string[]) => celebrations.updateCovers(slug, uris),
    onSuccess: () => {
      setFlash({ kind: 'ok', message: 'Cover photos updated.' });
      invalidate();
    },
    onError: (e) =>
      setFlash({ kind: 'error', message: e instanceof ApiError ? e.message : 'Could not upload the photos.' }),
  });

  const saveSlug = useMutation({
    mutationFn: () => celebrations.setSlug(slug, linkSlug.trim()),
    onSuccess: (res) => {
      setFlash({ kind: 'ok', message: 'Your celebration link has been updated.' });
      invalidate();
      // The slug is the route param, so the screen has to follow it.
      router.replace(`/celebration/${res.slug}`);
    },
    onError: (e) => setFlash({ kind: 'error', message: e instanceof ApiError ? e.message : 'Could not update the link.' }),
  });

  const applyTemplate = useMutation({
    mutationFn: (templateId: number) => celebrations.applyTemplate(slug, { template_id: templateId }),
    onSuccess: invalidate,
    onError: (e) => Alert.alert('Could not apply', e instanceof ApiError ? e.message : 'Please try again.'),
  });

  // Debounced availability check, as the web slug editor does.
  useEffect(() => {
    const candidate = linkSlug.trim();

    if (!candidate || candidate === c.slug) {
      setLinkCheck(null);
      return;
    }

    const timer = setTimeout(() => {
      celebrations
        .checkSlug(slug, candidate)
        .then(setLinkCheck)
        .catch(() => setLinkCheck(null));
    }, 450);

    return () => clearTimeout(timer);
  }, [linkSlug, slug, c.slug]);

  async function pickCovers() {
    const res = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsMultipleSelection: true,
      selectionLimit: 4,
      quality: 0.85,
    });

    if (!res.canceled && res.assets.length) {
      uploadCovers.mutate(res.assets.map((a) => a.uri));
    }
  }

  const templates = Array.isArray(page.templates) ? [] : page.templates.data;

  return (
    <View>
      {flash ? <Flash kind={flash.kind} message={flash.message} /> : null}

      {/* ── Cover photos ── */}
      <SectionHeader
        title="Cover photos"
        subtitle="Up to four. The first one leads the page."
        action={
          <Button
            title="Choose"
            variant="outline"
            size="sm"
            icon="image-plus"
            loading={uploadCovers.isPending}
            onPress={pickCovers}
          />
        }
      />

      {/* ── Page details ── */}
      <View style={{ marginTop: spacing.lg }}>
        <SectionHeader title="Page details" subtitle="What guests see at the top of the page" />

        <Field label="Page title" value={title} onChangeText={setTitle} />
        <Field label="Who it's for" value={celebrantName} onChangeText={setCelebrantName} />

        <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
          OCCASION
        </Txt>
        <View style={styles.chipRow}>
          {(['birthday', 'wedding', 'memorial', 'graduation', 'anniversary', 'baby_shower', 'other'] as const).map(
            (key) => {
              const selected = type === key;

              return (
                <Pressable
                  key={key}
                  accessibilityRole="radio"
                  accessibilityState={{ selected }}
                  onPress={() => setType(key)}
                  style={[styles.chip, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
                >
                  <Txt variant="small" color={selected ? colors.primary : colors.muted} style={{ fontWeight: '700' }}>
                    {celebrationType(key).label}
                  </Txt>
                </Pressable>
              );
            },
          )}
        </View>

        <View style={{ height: spacing.lg }} />

        <Field
          label="Description"
          value={description}
          onChangeText={setDescription}
          multiline
          style={{ minHeight: 90, textAlignVertical: 'top' }}
          placeholder="Tell guests what this celebration is about."
        />

        <Field label="Venue" value={venue} onChangeText={setVenue} placeholder="Where it's happening" />

        <Field
          label="Start date"
          value={startDate}
          onChangeText={setStartDate}
          placeholder="YYYY-MM-DD"
          keyboardType="numbers-and-punctuation"
        />
        <Field
          label="End date"
          value={endDate}
          onChangeText={setEndDate}
          placeholder="YYYY-MM-DD"
          keyboardType="numbers-and-punctuation"
        />

        {/* ── Visibility ── */}
        <View style={styles.switchRow}>
          <View style={{ flex: 1, paddingRight: spacing.md }}>
            <Txt variant="bodyStrong">Public page</Txt>
            <Txt variant="small" color={colors.muted} style={{ marginTop: 3, lineHeight: 18 }}>
              Public pages can appear in Discover. Private ones are only reachable by link.
            </Txt>
          </View>
          <Switch
            value={isPublic}
            onValueChange={setIsPublic}
            trackColor={{ true: colors.primary, false: colors.line }}
            thumbColor={colors.white}
          />
        </View>

        <Txt variant="tiny" color={colors.muted} style={{ marginBottom: 8, letterSpacing: 0.4 }}>
          STATUS
        </Txt>
        <View style={styles.chipRow}>
          {STATUSES.map((s) => {
            const selected = status === s;

            return (
              <Pressable
                key={s}
                accessibilityRole="radio"
                accessibilityState={{ selected }}
                onPress={() => setStatus(s)}
                style={[styles.chip, selected && { borderColor: colors.primary, backgroundColor: colors.primaryFaint }]}
              >
                <Txt variant="small" color={selected ? colors.primary : colors.muted} style={{ fontWeight: '700' }}>
                  {s === 'published' ? 'Live' : s === 'closed' ? 'Closed' : 'Draft'}
                </Txt>
              </Pressable>
            );
          })}
        </View>

        <Button
          title="Save page details"
          full
          loading={save.isPending}
          style={{ marginTop: spacing.xl }}
          onPress={() => {
            setFlash(null);
            save.mutate();
          }}
        />
      </View>

      {/* ── Custom link ── */}
      <View style={{ marginTop: spacing['2xl'] }}>
        <SectionHeader title="Celebration link" subtitle="The address guests use to find this page" />

        <Field
          label="Link"
          value={linkSlug}
          onChangeText={(t) => setLinkSlug(t.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
          autoCapitalize="none"
          hint={
            linkCheck === null
              ? 'Lowercase letters, numbers and hyphens.'
              : linkCheck.available
                ? '✓ That link is available.'
                : linkCheck.reason === 'taken'
                  ? 'That link is already taken — try another.'
                  : 'Use lowercase letters, numbers and hyphens only.'
          }
        />

        <Button
          title="Update link"
          variant="outline"
          loading={saveSlug.isPending}
          disabled={!linkSlug.trim() || linkSlug === c.slug || linkCheck?.available === false}
          onPress={() => saveSlug.mutate()}
        />
      </View>

      {/* ── Theme ── */}
      {templates.length > 0 ? (
        <View style={{ marginTop: spacing['2xl'] }}>
          <SectionHeader title="Theme" subtitle="How the page looks to guests" />

          <View style={styles.chipRow}>
            {templates.map((t) => {
              const selected = page.template?.data.id === t.id;

              return (
                <Pressable
                  key={t.id}
                  accessibilityRole="radio"
                  accessibilityState={{ selected }}
                  onPress={() => applyTemplate.mutate(t.id)}
                  style={[
                    styles.themeChip,
                    { backgroundColor: t.page_bg || colors.surface2 },
                    selected && { borderColor: colors.primary, borderWidth: 2 },
                  ]}
                >
                  <Txt variant="small" color={t.text_primary || colors.ink} style={{ fontWeight: '700' }}>
                    {t.name}
                  </Txt>
                </Pressable>
              );
            })}
          </View>

          {page.template ? (
            <Pressable onPress={() => celebrations.resetTemplate(slug).then(invalidate)} style={styles.resetRow}>
              <MaterialCommunityIcons name="restore" size={15} color={colors.muted} />
              <Txt variant="small" color={colors.muted}>
                Reset to the default look
              </Txt>
            </Pressable>
          ) : null}
        </View>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  chipRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: spacing.sm,
  },
  chip: {
    paddingHorizontal: spacing.md,
    paddingVertical: 9,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.pill,
  },
  themeChip: {
    paddingHorizontal: spacing.lg,
    paddingVertical: spacing.md,
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
  },
  switchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: spacing.lg,
    marginBottom: spacing.lg,
    borderTopWidth: 1,
    borderBottomWidth: 1,
    borderColor: colors.line2,
  },
  resetRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.lg,
  },
});

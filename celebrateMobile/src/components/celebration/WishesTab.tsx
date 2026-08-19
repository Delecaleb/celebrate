import MaterialCommunityIcons from '@expo/vector-icons/MaterialCommunityIcons';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Image } from 'expo-image';
import * as ImagePicker from 'expo-image-picker';
import { useRouter } from 'expo-router';
import { useVideoPlayer, VideoView } from 'expo-video';
import { useState } from 'react';
import { Pressable, StyleSheet, TextInput, View } from 'react-native';

import { ApiError } from '../../api/client';
import { comments as commentsApi } from '../../api/endpoints';
import type { CelebrationPage, Comment } from '../../api/types';
import { initials, relative } from '../../lib/format';
import { colors, radii, spacing } from '../../theme';
import { Button, EmptyState, Flash, Txt } from '../ui';

/**
 * The wishes wall — the page's busiest tab.
 *
 * A composer at the top (text, plus one photo or one video), then every wish.
 * Open to guests: posting does not require an account, matching the web.
 */
export function WishesTab({ page, slug }: { page: CelebrationPage; slug: string }) {
  const router = useRouter();
  const queryClient = useQueryClient();
  const celebration = page.celebration.data;

  const [message, setMessage] = useState('');
  const [imageUri, setImageUri] = useState<string | null>(null);
  const [videoUri, setVideoUri] = useState<string | null>(null);
  const [anonymous, setAnonymous] = useState(false);
  const [flash, setFlash] = useState<{ kind: 'ok' | 'error'; message: string } | null>(null);

  const post = useMutation({
    mutationFn: () =>
      commentsApi.create({
        celebration_id: celebration.id,
        comment: message.trim() || undefined,
        anonymous,
        imageUri: imageUri ?? undefined,
        videoUri: videoUri ?? undefined,
      }),
    onSuccess: () => {
      setMessage('');
      setImageUri(null);
      setVideoUri(null);
      setFlash({ kind: 'ok', message: 'Your wish has been posted.' });
      queryClient.invalidateQueries({ queryKey: ['celebration', slug] });
    },
    onError: (e) =>
      setFlash({
        kind: 'error',
        message: e instanceof ApiError ? e.message : 'Could not post your wish.',
      }),
  });

  async function pickImage() {
    const res = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      quality: 0.8,
    });

    if (!res.canceled && res.assets[0]) {
      setImageUri(res.assets[0].uri);
      // Only one kind of media per wish — the API accepts an image or a video.
      setVideoUri(null);
    }
  }

  async function pickVideo() {
    const res = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['videos'],
      quality: 0.8,
    });

    if (!res.canceled && res.assets[0]) {
      setVideoUri(res.assets[0].uri);
      setImageUri(null);
    }
  }

  const canPost = !!message.trim() || !!imageUri || !!videoUri;
  const wishes = page.comments.data;

  return (
    <View>
      {/* ── Composer ── */}
      {celebration.allow_wishes === false && !page.is_owner ? (
        <View style={styles.closed}>
          <MaterialCommunityIcons name="lock-outline" size={15} color={colors.muted} />
          <Txt variant="small" color={colors.muted}>
            The celebrant has turned off new wishes for this page.
          </Txt>
        </View>
      ) : (
        <View style={styles.composer}>
          {flash ? <Flash kind={flash.kind} message={flash.message} /> : null}

          <TextInput
            value={message}
            onChangeText={setMessage}
            multiline
            placeholder={`Write a wish for ${celebration.celebrant_name ?? 'the celebrant'}…`}
            placeholderTextColor={colors.muted2}
            style={styles.input}
            maxLength={1000}
          />

          {imageUri ? (
            <Preview onRemove={() => setImageUri(null)}>
              <Image source={{ uri: imageUri }} style={styles.previewMedia} contentFit="cover" />
            </Preview>
          ) : null}

          {videoUri ? (
            <Preview onRemove={() => setVideoUri(null)}>
              <VideoPreview uri={videoUri} />
            </Preview>
          ) : null}

          <View style={styles.composerBar}>
            <View style={{ flexDirection: 'row', gap: spacing.xs }}>
              <ComposerAction icon="image-outline" label="Add a photo" onPress={pickImage} />
              <ComposerAction icon="video-outline" label="Add a video" onPress={pickVideo} />
              <ComposerAction
                icon="record-circle-outline"
                label="Record a video wish"
                onPress={() => router.push({ pathname: '/modal/recorder', params: { slug, id: String(celebration.id) } })}
              />
              <ComposerAction
                icon={anonymous ? 'incognito' : 'incognito-off'}
                label={anonymous ? 'Posting anonymously' : 'Post anonymously'}
                active={anonymous}
                onPress={() => setAnonymous((v) => !v)}
              />
            </View>

            <Button
              title="Post"
              size="sm"
              disabled={!canPost}
              loading={post.isPending}
              onPress={() => {
                setFlash(null);
                post.mutate();
              }}
            />
          </View>
        </View>
      )}

      {/* ── The wall ── */}
      {wishes.length === 0 ? (
        <EmptyState
          icon="message-outline"
          title="No wishes yet"
          body="Be the first to say something — a line, a photo or a short video all work."
        />
      ) : (
        <View style={{ marginTop: spacing.xl, gap: spacing.md }}>
          {wishes.map((wish) => (
            <WishCard key={wish.id} wish={wish} slug={slug} />
          ))}
        </View>
      )}
    </View>
  );
}

function WishCard({ wish, slug }: { wish: Comment; slug: string }) {
  const queryClient = useQueryClient();
  // Optimistic, because a like should feel instant.
  const [liked, setLiked] = useState(false);
  const [likes, setLikes] = useState(wish.like_count);

  const react = useMutation({
    mutationFn: () => commentsApi.react(wish.id),
    onSuccess: (res) => {
      setLiked(res.reacted);
      setLikes(res.like_count);
    },
    onError: () => {
      // Put it back the way it was.
      setLiked((v) => !v);
      setLikes(wish.like_count);
      queryClient.invalidateQueries({ queryKey: ['celebration', slug] });
    },
  });

  return (
    <View style={styles.wishCard}>
      <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
        <View style={styles.avatar}>
          {wish.author_photo ? (
            <Image source={{ uri: wish.author_photo }} style={{ width: '100%', height: '100%' }} contentFit="cover" />
          ) : (
            <Txt variant="tiny" color={colors.primary}>
              {initials(wish.author)}
            </Txt>
          )}
        </View>

        <View style={{ flex: 1 }}>
          <Txt variant="small" style={{ fontWeight: '700' }}>
            {wish.author}
          </Txt>
          <Txt variant="small" color={colors.muted2} style={{ fontSize: 11, marginTop: 1 }}>
            {relative(wish.created_at, wish.created_at_human)}
          </Txt>
        </View>

        {wish.is_pinned ? (
          <MaterialCommunityIcons name="pin" size={14} color={colors.secondary} />
        ) : null}
      </View>

      {wish.message ? (
        <Txt variant="body" style={{ marginTop: spacing.md, lineHeight: 22 }}>
          {wish.message}
        </Txt>
      ) : null}

      {wish.media_url && wish.media_type === 'video' ? (
        <View style={styles.wishMedia}>
          <VideoPreview uri={wish.media_url} />
        </View>
      ) : wish.media_url ? (
        <Image source={{ uri: wish.media_url }} style={styles.wishMedia} contentFit="cover" transition={180} />
      ) : null}

      <Pressable
        accessibilityRole="button"
        accessibilityLabel={liked ? 'Remove your reaction' : 'React with love'}
        onPress={() => {
          setLiked((v) => !v);
          react.mutate();
        }}
        style={styles.likeRow}
      >
        <MaterialCommunityIcons
          name={liked ? 'heart' : 'heart-outline'}
          size={16}
          color={liked ? colors.danger : colors.muted2}
        />
        <Txt variant="small" color={liked ? colors.danger : colors.muted}>
          {likes > 0 ? likes : 'Love this'}
        </Txt>
      </Pressable>
    </View>
  );
}

/** Small inline video player, used for previews and for video wishes. */
function VideoPreview({ uri }: { uri: string }) {
  const player = useVideoPlayer(uri, (p) => {
    p.loop = false;
  });

  return <VideoView player={player} style={styles.previewMedia} nativeControls contentFit="cover" />;
}

function Preview({ children, onRemove }: { children: React.ReactNode; onRemove: () => void }) {
  return (
    <View style={styles.preview}>
      {children}
      <Pressable accessibilityRole="button" accessibilityLabel="Remove" onPress={onRemove} style={styles.previewClose}>
        <MaterialCommunityIcons name="close" size={14} color={colors.white} />
      </Pressable>
    </View>
  );
}

function ComposerAction({
  icon,
  label,
  onPress,
  active,
}: {
  icon: any;
  label: string;
  onPress: () => void;
  active?: boolean;
}) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      onPress={onPress}
      hitSlop={6}
      style={({ pressed }) => [
        styles.composerAction,
        active && { backgroundColor: colors.primaryLight },
        pressed && { opacity: 0.6 },
      ]}
    >
      <MaterialCommunityIcons name={icon} size={18} color={active ? colors.primary : colors.muted} />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  composer: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.md,
  },
  input: {
    minHeight: 76,
    fontSize: 15,
    color: colors.ink,
    textAlignVertical: 'top',
  },
  composerBar: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.line2,
  },
  composerAction: {
    width: 32,
    height: 32,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: radii.pill,
  },
  preview: {
    marginTop: spacing.md,
    width: 120,
    height: 120,
    borderRadius: radii.soft.sm,
    overflow: 'hidden',
    backgroundColor: colors.surface2,
  },
  previewMedia: {
    width: '100%',
    height: '100%',
  },
  previewClose: {
    position: 'absolute',
    top: 5,
    right: 5,
    width: 22,
    height: 22,
    borderRadius: radii.pill,
    backgroundColor: 'rgba(0,0,0,0.6)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  closed: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: spacing.sm,
    padding: spacing.md,
    backgroundColor: colors.surface2,
    borderRadius: radii.card,
  },
  wishCard: {
    borderWidth: 1,
    borderColor: colors.line,
    borderRadius: radii.card,
    padding: spacing.lg,
  },
  avatar: {
    width: 32,
    height: 32,
    borderRadius: radii.pill,
    backgroundColor: colors.primaryLight,
    alignItems: 'center',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  wishMedia: {
    width: '100%',
    height: 220,
    marginTop: spacing.md,
    borderRadius: radii.soft.sm,
    overflow: 'hidden',
    backgroundColor: colors.surface2,
  },
  likeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: spacing.md,
    paddingTop: spacing.md,
    borderTopWidth: 1,
    borderTopColor: colors.line2,
  },
});
